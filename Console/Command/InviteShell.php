<?php
/**
 * Class InviteShell
 *
 * Process or send playlist invites
 *
 */
App::uses('AppModel', 'Model');
App::uses('HttpSocket','Network/Http');
App::import('Core', 'ConnectionManager');
class InviteShell extends Shell {

    var $uses = array(
        'JobQueue',
        'Membership',
        'Playlist',
        'PlaylistItem',
        'PlaylistInvite',
        'PlaylistMember',
        'Video',
        'Audio',
    );

    var $status = array();

    public function perform() {
        $this->initialize();
        $this->{array_shift($this->args)}();
    }

    public function send_invites()
    {
        $playlist_id = $this->args[0];
        $mail_id = $this->args[1];
        $isTest = (!empty($this->args[2]) && $this->args[2] == true) ? true : false;

        $users = $this->PlaylistInvite->find('all',array(
            'conditions' => array('mail_id' => $mail_id),
            'contain' => array()
        ));
        //$this->log($users,'pm');

        $userIDs = Hash::extract($users, '{n}.PlaylistInvite.user_id');
        $memberships = $this->Membership->find('list',array(
            'conditions' => array(
                'client_id' => $users[0]['PlaylistInvite']['client_id'],
                'user_id' => $userIDs
            ),
            'fields' => array('user_id','id')
        ));
        //$this->log($memberships,'pm');

        // SETUP Playlist Membership data and lookup
        // @see http://nickology.com/2012/07/03/php-faster-array-lookup-than-using-in_array/
        $playlistMembersTemp = $this->PlaylistMember->find('list', [
            'conditions' => ['PlaylistMember.playlist_id' => $playlist_id],
            'fields' => ['PlaylistMember.user_id']
        ]);

        $playlistMembers = [];
        foreach (array_values($playlistMembersTemp) as $member) {
            $playlistMembers[$member] = 1;
        }

        foreach($users as $user) {
            // Create PlaylistMember records for any user that does not already have one
            if (!$isTest && !isset($playlistMembers[$user['PlaylistInvite']['user_id']])) {
                $this->PlaylistMember->unbindModel(['belongsTo' => ['Playlist', 'Membership']]);
                $this->PlaylistMember->create();
                $this->PlaylistMember->set([
                        'member_id' => $memberships[$user['PlaylistInvite']['user_id']],
                        'playlist_id' => $playlist_id,
                        'user_id' => $user['PlaylistInvite']['user_id']
                    ]
                );
                $this->PlaylistMember->save();
            }
        }


        $HttpSocket = new HttpSocket();
        $data = array();
        //Will be 'https://services.fordela.com/api/invite/send/'.$playlist_id.'/'.$mail_id
        //$results = $HttpSocket->post('http://slimapi.loc/api/invite/send/'.$playlist_id.'/'.$mail_id, $data);
        $results = $HttpSocket->post('https://services.fordela.com/api/invite/send/'.$playlist_id.'/'.$mail_id, $data);

        $this->status['description'] = 'Emails sent for playlist_id: '.$playlist_id.' and mail_id: '.$mail_id;
        $this->status['status'] = 'Finished';
        //$jobId = end($this->args);
        $jobId = $this->args[3]; //end($this->args);
        $this->JobQueue->updateJob($jobId,$this->status);

        //TODO: Populate metadata (VideoIDs) of items being sent
        $this->Playlist->contain();
        $playlist = $this->Playlist->findById($playlist_id,'id, name, type');
        $Model = $playlist['Playlist']['type'];
        $itemIDs = $this->PlaylistItem->find('list',array(
            'conditions' => array('PlaylistItem.playlist_id' => $playlist_id),
            'fields' => array('PlaylistItem.foreign_id')));

        if ($Model == 'Audio') {
            $conditions = array('Audio.id' => array_values($itemIDs));
        } else {
            $conditions = array('Video.id' => array_values($itemIDs));
        }

        $items = $this->{$Model}->find('list', array(
            'conditions' => $conditions,
            'fields' => array('id','title')));

        $metadata = array($Model => $items);
        $json = json_encode($metadata);

        $db = ConnectionManager::getDataSource('default');
        $jsonString = $db->value($json, 'string');
        $this->PlaylistInvite->updateAll(
            array('PlaylistInvite.meta_data' => $jsonString),
            array('PlaylistInvite.mail_id' => $mail_id)
        );
    }

    public function metadata()
    {
        $mailIdList = $this->PlaylistInvite->find('list', [
            'conditions' => ['PlaylistInvite.meta_data' => null],
            'fields' => ['mail_id']
        ]);
        $mailIdList = array_unique($mailIdList);
        $total = count($mailIdList);

        $this->out('Mailing count with metadata needing updating: '.$total);
        $i = 0;
        foreach($mailIdList as $mail_id) {
            $i++;
            $this->out($i.' / '.$total);
            $r = $this->PlaylistInvite->find('first', [
                'conditions' => ['PlaylistInvite.mail_id' => $mail_id]
            ]);
            $playlist_id = $r['PlaylistInvite']['playlist_id'];

            $this->Playlist->contain();
            $playlist = $this->Playlist->findById($playlist_id,'id, name, type');
            $Model = $playlist['Playlist']['type'];
            $itemIDs = $this->PlaylistItem->find('list',array(
                'conditions' => array('PlaylistItem.playlist_id' => $playlist_id),
                'fields' => array('PlaylistItem.foreign_id')));

            if ($Model == 'Audio') {
                $conditions = array('Audio.id' => array_values($itemIDs));
            } else {
                $conditions = array('Video.id' => array_values($itemIDs));
            }

            $items = $this->{$Model}->find('list', array(
                'conditions' => $conditions,
                'fields' => array('id','title')));

            $metadata = array($Model => $items);
            $json = json_encode($metadata);

            $db = ConnectionManager::getDataSource('default');
            $jsonString = $db->value($json, 'string');
            $this->PlaylistInvite->updateAll(
                array('PlaylistInvite.meta_data' => $jsonString),
                array('PlaylistInvite.mail_id' => $mail_id)
            );

        }

        $this->out('Finished');
    }
}
