<?php

App::uses('AppModel', 'Model');

/**
 * PlaylistInvite Model
 *
 * @property Playlist $Playlist
 * @property User $User
 */
class PlaylistInvite extends AppModel
{
    /**
     * Display field
     *
     * @var string
     */
    public $displayField = true;

    /**
     * belongsTo associations
     *
     * @var array
     */
    public $belongsTo = array(
        'Playlist' => array(
            'className' => 'Playlist',
            'foreignKey' => 'playlist_id',
            'conditions' => '',
            'fields' => '',
            'order' => ''
        ),
        'User' => array(
            'className' => 'User',
            'foreignKey' => 'user_id',
            'conditions' => '',
            'fields' => '',
            'order' => ''
        )
    );


    /**
     * @var array
     */
    public $actsAs = [
        //'Search.Search',
        'Containable'
    ];

    /**
     * Search filter args
     *
     */
    public $filterArgs = array(
        array(
            'name' => 'search_name',
            'type' => 'query',
            'method' => 'searchName'
        ),
        array(
            'name' => 'search_email',
            'type' => 'query',
            'method' => 'searchEmail'
        ),
        array(
            'name' => 'search_company',
            'type' => 'query',
            'method' => 'searchCompany'
        ),
        array(
            'name' => 'search_sent_start',
            'type' => 'query',
            'method' => 'searchSent'
        ),
        array(
            'name' => 'search_sent_end',
            'type' => 'query',
            'method' => 'searchSent'
        ),
        array(
            'name' => 'search_created_start',
            'type' => 'query',
            'method' => 'searchCreated'
        ),
        array(
            'name' => 'search_created_end',
            'type' => 'query',
            'method' => 'searchCreated'
        ),
    );


    /**
     * This effectively clones the previous invite
     *      1) Clones the invite row
     *      2) Clears id, sent, created, error_log
     *      3) Creates a new mail_id
     *      4) Saves as new row
     *
     * @param array|string $ids Invite Id(s)
     * @return array $invite
     */
    public function resend($ids, $mailId = null)
    {
        if (null === $mailId) {
            $mailId = $this->generateMailId();
        }

        $this->contain();
        $this->read(null, $id);

        // Clears error and sent
        $this->set('sent', null);
        $this->set('error', null);

        // Increase resends count
        $this->set('resends', $this->resends + 1);

        // Add new mail id so it can get picked up
        // note: send_id stays the same. It's used for tracking the original send group
        $this->set('mail_id', $mailId);

        // todo: add to queue to resend
        // push $mailId into the send-playlist-invite queue

        // Save invite
        return $this->save();
    }

    /**
     * Resends all invites for a send id
     *
     * @param string $sendId
     * @return array
     */
    public function resendAll($sendId)
    {
        if (empty($sendId)) {
            return [];
        }

        $db     = $this->getDataSource();
        $result = $this->updateAll([
            'PlaylistInvite.sent' => null,
            'PlaylistInvite.error' => null,
            'PlaylistInvite.resends' => 'PlaylistInvite.resends + 1',
            'PlaylistInvite.mail_id' => $db->value($this->generateMailId(), 'string'),
        ], [
            'PlaylistInvite.send_id' => $sendId
        ]);

        // todo: add to queue to resend
        // push $mailId into the send-playlist-invite queue

        return $result;
    }


    /**
     * Sends a playlist to email addresses.
     *
     * Internally saves into the database to be picked up by a sending process.
     *
     * @param array $playlistData playlist, from Playlist::find()
     * @param array $emails array of email addresses
     * @param string $from email from
     * @param string $subject email subject
     * @param string $body email body
     * @param bool $extendLogin Should the login be extended to 90 days?
     * @param bool $hideVideos Should the individual videos be
     * @param bool $isTest Is this a test email
     */
    public function send(
        $playlistId,
        $emails,
        $from,
        $subject,
        $body,
        $extendLogin = false,
        $hideVideos = true,
        $isTest = false
    ) {
        $playlist = ClassRegistry::init('Playlist');
        $user = ClassRegistry::init('User');
        $playlistItem = ClassRegistry::init('PlaylistItem');
        $playlistMember = ClassRegistry::init('PlaylistMember');

        $mailId = $this->generateMailId();
        $playlistData = $playlist->findById($playlistId);
        $playlistId = $playlistData['Playlist']['id'];
        $clientId = $playlistData['Playlist']['client_id'];

        // Change association on the fly to get the specifc client membership for each user
        $user->unbindModel(['hasAndBelongsToMany' => ['Client']]);
        $user->bindModel([
            'hasMany' => [
                'Membership' => [
                    'conditions' => [
                        'Membership.client_id' => $clientId
                    ],
                    'fields' => ['Membership.id']
                ]
            ]
        ]);

        $users = $user->find('all', [
            'conditions' => ['User.username' => $emails],
            'fields' => ['id', 'username']
        ]);

        $itemCount = $playlistItem->find('count', [
            'conditions' => ['playlist_id' => $playlistId]
        ]);

        // SETUP Playlist Membership data and lookup
        // @see http://nickology.com/2012/07/03/php-faster-array-lookup-than-using-in_array/
        $playlistMembersTemp = $playlistMember->find('list', [
            'conditions' => ['PlaylistMember.playlist_id' => $playlistId],
            'fields' => ['PlaylistMember.user_id']
        ]);

        $playlistMembers = [];
        foreach (array_values($playlistMembersTemp) as $member) {
            $playlistMembers[$member] = 1;
        }

        // Iterate users, creating each PlaylistInvite and
        // adding to PlaylistMembers if not already there
        foreach ($users as $user) {

            // Create PlaylistMember records for any user that does not already have one
            if (!isset($playlistMembers[$user['User']['id']])) {
                $playlistMember->unbindModel(['belongsTo' => ['Playlist', 'Membership']]);
                $playlistMember->create();
                $playlistMember->set([
                        'member_id' => $user['Membership'][0]['id'],
                        'playlist_id' => $playlistId,
                        'user_id' => $user['User']['id']
                    ]
                );
                $playlistMember->save();
            }

            // Create PlaylistInvite record for each user
            $this->create();
            $this->set([
                'mail_id' => $mailId,
                'send_id' => $mailId,
                'playlist_id' => $playlistId,
                'user_id' => $user['User']['id'],
                'to' => $user['User']['username'],
                'from' => $from,
                'subject' => $subject,
                'body' => $body,
                'item_count' => $itemCount,
                'extend_login' => $extendLogin,
                'hide_videos' => $hideVideos,
                'is_test' => $isTest,
            ]);
            $this->save();
        }

        // Update Playlist member_count (counter cache)
        $playlistMemberCount = $playlistMember->find('count', [
            'conditions' => ['playlist_id' => $playlistId]
        ]);

        $playlist->id = $playlistId;
        $playlist->saveField('member_count', $playlistMemberCount);
    }

    /**
     * Generates a unique mail id
     *
     * @param int|string $length = 10
     * @return string unique id
     */
    protected function generateMailId($length = 10)
    {
        return md5(uniqid(mt_rand(), true));
    }

    /**
     * Returns search conditions for searching email
     *
     * @param array $input
     * @return array
     */
    public function searchEmail($input)
    {
        return [
            'to LIKE ' => '%' . $input['search_email'] . '%'
        ];
    }

    /**
     * Returns search conditions for searching company
     *
     * @param array $input
     * @return array
     */
    public function searchCompany($input)
    {
        return [
            'User.company LIKE ' => '%' . $input['search_company'] . '%'
        ];
    }


    /**
     * Returns search conditions for searching name
     *
     * @param array $input
     * @return array
     */
    public function searchName($input)
    {
        return [
            'User.name LIKE ' => '%' . $input['search_name'] . '%'
        ];
    }

    /**
     * Returns search conditions for searching sent
     *
     * @param array $input
     * @return array
     */
    public function searchSent($input)
    {
        $cond = [];

        if (isset($input['search_sent_start'])) {
            $cond['PlaylistInvite.sent >='] = $input['search_sent_start'];
        }

        if (isset($input['search_sent_end'])) {
            $cond['PlaylistInvite.sent <='] = $input['search_sent_end'];
        }

        return $cond;
    }

    /**
     * Returns search conditions for searching created
     *
     * @param array $input
     * @return array
     */
    public function searchCreated($input)
    {
        $cond = [];

        if (isset($input['search_created_start'])) {
            $cond['PlaylistInvite.created >='] = $input['search_created_start'];
        }

        if (isset($input['search_created_end'])) {
            $cond['PlaylistInvite.created <='] = $input['search_created_end'];
        }

        return $cond;
    }
}
