<?php
/**
 * Class InviteShell
 *
 * Process or send playlist invites
 *
 */
App::uses('HttpSocket','Network/Http');
class InviteShell extends Shell {

    var $uses = array(
        'JobQueue',
        //'Client',
        //'Membership',
        //'Playlist',
        //'PlaylistMember',
        //'Token',
        //'User',
        //'PlaylistInvite'
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
        $this->log($this->args, 'invites');

        //TODO: Populate metadata (VideoIDs) of items being sent
        //TODO: POST to services.fordela.com to process the mailing
        $HttpSocket = new HttpSocket();
        $data = array();
        //Will be 'https://services.fordela.com/api/invite/send/'.$playlist_id.'/'.$mail_id
        $results = $HttpSocket->post('http://slimapi.loc/api/invite/send/'.$playlist_id.'/'.$mail_id, $data);
        $this->log($results, 'invites');


        $this->status['description'] = 'Emails sent for playlist_id: '.$playlist_id.' and mail_id: '.$mail_id;
        $this->status['status'] = 'Finished';
        $jobId = end($this->args);
        $this->JobQueue->updateJob($jobId,$this->status);
    }
}
