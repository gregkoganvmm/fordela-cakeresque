<?php
use Aws\Common\Aws;
App::import('Core', 'Controller');
App::uses('ComponentCollection','Controller');
App::uses('AwsComponent','Controller/Component');

class CleanupShell extends Shell {

    var $status = array();
    public $uses = array(
        'JobQueue',
        'Client',
        'Audio',
        'Video',
        'VideoVersion',
        'Playlist',
        'PlaylistItem',
        'PlaylistMember',
        'VideoMember',
        'Membership',
        'UserType',
        'Attachment',
        'Annotation',
        'VideoAnnotation',
        'EncodingProfile', // Encoder.Profile
        'MetadataField', //'Metadata.MetadataField'
        'MetadataValue' //'Metadata.MetadataValue'
    );

    public function perform() {
        $this->initialize();
        $this->{array_shift($this->args)}();
    }

    // Make AwsComponent callable
    protected function _initAws() {
        $Collection = new ComponentCollection();
        return $this->Aws = new AwsComponent($Collection);
    }


    /*  Client record already gone. Delete remaining assets
    *   1) Video (video_id) -> Get VideoVersion, VideoMember
    *   2) Audio
    *   3) Playlist (playlist_id) -> Get PlaylistItem, PlaylistMember, Annotation, VideoAnnotation
    *   4) Membership (client_id) -> Get Memberships (not Users)
    *   5) UserType
    *   6) Attachments (client_id) -> Gets Video and Playlist attachments
    *   7) EncodingProfile
    *   8) Metadata - MetadataFields, MetadataValue
    *
    *   TODO:
    *   9) S3 assets (2d/3d/)
    */
    public function client_delete() {
        /*
        // Only needed if running manually from command line
        if(empty($this->args[0])) {
            $this->out('Error! Client ID argument missing.');
            die;
        }*/
        $client_id = $this->args[0];
        $this->out('Starting - Running scripts to delete Client ID: '. $client_id);
        $this->_remove_client_video($client_id);
        $this->_remove_client_audio($client_id);
        $this->_remove_client_playlist($client_id);
        $this->_remove_client_membership($client_id);
        $this->_remove_client_usertype($client_id);
        $this->_remove_client_attachment($client_id);
        $this->_remove_client_profile($client_id);
        $this->_remove_client_metadata($client_id);
        $this->out('Finished - Client ID: '.$client_id.' deleted');
        //$this->_remove_client_s3assets($client_id);

        $this->status['status'] = 'Finished';
        $this->status['description'] = 'Client ID: '.$client_id.' deleted';
        $this->status['finished'] = date('Y-m-d H:i:s');
        $jobId = end($this->args);
        $this->JobQueue->updateJob($jobId,$this->status);
    }

    /**
    * TODO: this function is a work in progress
    */
    protected function _remove_client_s3assets($client_id) {
        $result = $this->Aws->get('S3','listObjects',array(
            // Bucket is required
            'Bucket' => 'archive.fordela.com',
            //'Delimiter' => 'string',
            //'Marker' => 'string',
            //'MaxKeys' => integer,
            //'Prefix' => '995',
        ));

        debug($result);


        /*$result = $client->deleteObjects(array(
            // Bucket is required
            'Bucket' => 'string',
            // Objects is required
            'Objects' => array(
                array(
                    // Key is required
                    'Key' => 'string',
                    'VersionId' => 'string',
                ),
                // ... repeated
            ),
            'Quiet' => true || false,
            'MFA' => 'string',
        ));*/
    }

    protected function _remove_client_video($client_id) {
        $videos = $this->Video->find('list',array('conditions' => array('Video.client_id' => $client_id)));
        $count = count($videos);
        if($count < 1){
            return $this->out('No Video records to delete');
        }
        $this->out($count.' Videos found');
        $this->out('Removing VideoVersion and VideoMember records from video_id');
        foreach($videos as $video_id => $title) {
            $this->VideoVersion->deleteAll(array('VideoVersion.video_id' => $video_id),false);
            $this->VideoMember->deleteAll(array('VideoMember.video_id' => $video_id),false);
        }
        $this->out('Removing '.$count.' Video records');
        $this->Video->deleteAll(array('Video.client_id' => $client_id),false);
        return;
    }

    protected function _remove_client_audio($client_id) {
        $conditions = array('Audio.client_id' => $client_id);
        $count = $this->Audio->find('count',array('conditions' => $conditions));
        if($count < 1) {
            return $this->out('No Audio records to delete');
        }
        $this->out('Removing '.$count.' Audio records');
        $this->Audio->deleteAll($conditions,false);
        return;
    }

    protected function _remove_client_playlist($client_id) {
        $playlists = $this->Playlist->find('list',array('conditions' => array('Playlist.client_id' => $client_id)));
        $count = count($playlists);
        if($count < 1) {
            return $this->out('No Playlist records to delete');
        }
        $this->out($count.' Playlists found');
        $this->out('Removing PlaylistItem, PlaylistMember, Annotation, and VideoAnnotation records from playlist_id');
        foreach($playlists as $playlist_id => $name) {
            $this->PlaylistItem->deleteAll(array('PlaylistItem.playlist_id' => $playlist_id),false);
            $this->PlaylistMember->deleteAll(array('PlaylistMember.playlist_id' => $playlist_id),false);
            $this->Annotation->deleteAll(array('Annotation.playlist_id' => $playlist_id),false);
            $this->VideoAnnotation->deleteAll(array('VideoAnnotation.playlist_id' => $playlist_id),false);
        }
        $this->out('Removing '.$count.' Playlist records');
        $this->Playlist->deleteAll(array('Playlist.client_id' => $client_id));
        return;
    }

    protected function _remove_client_membership($client_id) {
        $conditions = array('Membership.client_id' => $client_id);
        $count = $this->Membership->find('count',array('conditions' => $conditions));
        if($count < 1) {
            return $this->out('No Membership records to delete');
        }
        $this->out('Removing '.$count.' Membership records');
        $this->Membership->deleteAll($conditions,false);
        return;
    }

    protected function _remove_client_usertype($client_id) {
        $conditions = array('UserType.client_id' => $client_id);
        $count = $this->UserType->find('count',array('conditions' => $conditions));
        if($count < 1) {
            return $this->out('No UserType records');
        }
        $this->out('Removing '.$count.' UserType records');
        $this->UserType->deleteAll(array($conditions),false);
        return;
    }

    protected function _remove_client_attachment($client_id) {
        $conditions = array('Attachment.client_id' => $client_id);
        $count = $this->Attachment->find('count',array('conditions' => $conditions));
        if($count < 1) {
            return $this->out('No Attachment records to delete');
        }
        $this->out('Removing '.$count.' Attachment records');
        $this->Attachment->deleteAll($conditions,false);
        return;
    }

    protected function _remove_client_profile($client_id) {
        $conditions = array('EncodingProfile.client_id' => $client_id);
        $count = $this->EncodingProfile->find('count',array('conditions' => $conditions));
        if($count < 1) {
            return $this->out('No EncodingProfile records');
        }
        $this->out('Removing '.$count.' EncodingProfile records');
        $this->EncodingProfile->deleteAll(array($conditions),false);
        return;
    }

    protected function _remove_client_metadata($client_id) {
        $conditions = array('MetadataField.client_id' => $client_id);
        $fields = $this->MetadataField->find('list',array('conditions' => $conditions,'fields' => array('id','model')));
        $count = count($fields);
        if($count < 1) {
            return $this->out('No MetadataField records');
        }
        $this->out($count.' MetadataFields found');
        $this->out('Removing MetadataValue records');
        foreach($fields as $field_id => $model) {
            $cond = array('MetadataValue.metadata_field_id' => $field_id,'MetadataValue.model' => $model);
            $this->MetadataValue->deleteAll($cond,false);
        }
        $this->out('Removing '.$count.' MetadataField records');
        $this->MetadataField->deleteAll($conditions,false);
        return;
    }


    /**
    * For video records flagged "deleted" delete the Source and version
    * objects and records off of S3
    */
    public function deleted_video() {
        $video_id = (isset($this->args[0])) ? $this->args[0] : 17628;
        
        $this->Attachment->removeVideoAttachments($video_id);

        $this->Video->bindModel(array('hasMany'=>array('VideoVersion')));
        $video = $this->Video->read(null,$video_id);


        $this->_initAws();

        //Delete version objects and records if there are any
        if(!empty($video['VideoVersion'])){
            foreach($video['VideoVersion'] as $version){
                if(!empty($version['host'])){
                    $urlInfo = parse_url($version['host']);
                    $bucket = $urlInfo['host'];

                    // ISM files from ss-media
                    if(strpos($version['filename'],'_MBR_') !== false && $version['ext'] == strtolower('mp4')){
                        // How to handle deleting ISM objects from ss-media
                        $bucket = 'ss-media.fordela.com';

                        // Needs to remove all objects down key path
                        // Take off "/ondemand/"  -> sample how it should read '1478/11050/'
                        $prefix = str_replace(array('/ondemand/','ondemand/'),'',$version['dir']);

                        // list objects along a key path
                        $list = $this->Aws->get('S3','listObjects',array('Bucket' => $bucket,'Prefix' => $prefix));

                        // Setup objects array keys to delete
                        $objects = array();
                        foreach($list['Contents'] as $obj){
                            if(!empty($obj['Key'])){
                                $objects[] = array('Key',$obj['Key']);
                            }
                        }

                        // delete objects
                        $this->Aws->get('S3','deleteObjects',array(
                            'Bucket' => $bucket,
                            'Objects' => $objects
                        ));
                    }
                    // Normal MBR (bitrate_switching using mp4)
                    elseif (strpos($version['filename'],'_MBR_') !== false && $version['ext'] == strtolower('mp4')){
                        // Explode bitrates and delete each object
                        $bitrates = explode(',',$version['bitrate']);
                        foreach($bitrates as $bitrate){
                            $filename = str_replace('_MBR_','_'.$bitrate.'_',$version['filename']);
                            $key = $version['dir'].$filename;
                            $this->_delete_object($bucket, $key);
                        }
                    }
                    // HLS version
                    elseif (strpos($version['filename'],'_playlist_') !== false && $version['ext'] == strtolower('m3u8')) {
                        $this->_deleteHlsVersion($video['Video']['client_id'],$video_id);
                    }
                    // Regular versions -> that are not default demo files
                    else{
                        // Keep DEMO files
                        if($version['dir'] <> 'demo/') {
                            // Check and do not delete the Source file if another Source record exists
                            // using this filename
                            $srcMultipleRecords = $this->VideoVersion->find('first',array(
                                'conditions' => array(
                                    'VideoVersion.name' => 'Source',
                                    'VideoVersion.filename' => $version['filename'],
                                    'VideoVersion.id <>' => $version['id']
                                ),
                                'fields' => array('VideoVersion.id'),
                                'contain' => array()
                            ));
                            if(!isset($srcMultipleRecords['VideoVersion']['id'])){
                                $key = $version['dir'].$version['filename'];
                                $this->_delete_object($bucket, $key);
                            }
                        }
                    }

                    // Delete the version record last
                    $this->VideoVersion->delete($version['id']);
                }
            }
        }

        // Check and try one more time to delete the Source just in case there was no Source version record
        if(!isset($srcMultipleRecords['VideoVersion']['id'])){ // Skip if set for 2nd time

            //Account for TRIMMED records by checking and verifying one more time the Source is not used in another record!
            $secondSrcCheck = $this->VideoVersion->find('first',array(
                'conditions' => array(
                    'name' => 'Source',
                    'filename' => $video['Video']['archive'],
                    'dir' => $video['Video']['archive_dir'], // adding to check within same client
                    'video_id <>' => $video['Video']['id']
                ),
                'contain' => array()
            ));

            if(!$secondSrcCheck &&!empty($video['Video']['location']) && !empty($video['Video']['archive_dir'])){
                $locArr = parse_url($video['Video']['location']);
                $srcBucket = $locArr['host'];
                $srcKey = $video['Video']['archive_dir'].$video['Video']['archive'];
                $this->_initAws();
                $this->Aws->registerStreamWrapper();
                if(file_exists('s3://'.$srcBucket.'/'.$srcKey)){
                    $this->Aws->get('S3','deleteObject',array('Bucket'=>$srcBucket,'Key'=>$srcKey));
                }
            }
        }

        $this->status['status'] = 'Finished';
        $this->status['description'] = 'Source / Version objects deleted';
        $jobId = end($this->args);
        $this->JobQueue->updateJob($jobId,$this->status);

        $this->out('Video cleanup complete for video_id '.$video['Video']['id']);
    }

    /**
    * Deletes actual S3 objects and version record
    */
    public function _delete_object($bucket, $key){
        $this->_initAws();
        $this->out('Bucket: '.$bucket.' Key: '.$key);
        return $this->Aws->get('S3','deleteObject',array('Bucket'=>$bucket,'Key'=>$key));
    }

    public function _deleteHlsVersion($client_id,$video_id)
    {
        $s3 = Aws::factory(AWS_OREGON)->get('S3');
        $hasObjects = true;
        $objCount = 0;

        while($hasObjects) {
            $list = $s3->listObjects(array(
                'Bucket' => MEDIA_HLS,
                'Prefix' => $client_id.'/'.$video_id.'/'
            ));

            if( count($list['Contents']) > 0) {
                $toDeleteArray = array();
                foreach($list['Contents'] as $k => $object) {
                    $objCount++;
                    // Setup array of objects to delete
                    $toDeleteArray[] = array('Key' => $object['Key']);
                }
                // Now delete those objects
                $s3->deleteObjects(array(
                    'Bucket' => MEDIA_HLS,
                    'Objects' => $toDeleteArray
                ));
            } else {
                $hasObjects = false;
            }
        }
        return;
    }

    /**
    * Using to clean up Stellar titles
    */
    public function title_update(){
        $videos = $this->Video->find('list',array(
            'conditions'=>array(
                'client_id'=>1068,
                'title LIKE'=>'%.flv%',
                'active'=>1,
                'deleted'=>0
            ),
            'limit'=>500
        ));
        if(empty($videos)){
            $this->out('No result!');
            die;
        }

        $this->out(count($videos).' titles ready to update');

        $count = 0;
        foreach($videos as $video_id => $title){
            $info = pathinfo($title);
            $new_title = $info['filename'];

            $this->Video->id = $video_id;
            if($this->Video->savefield('title',$new_title)){
                $count++;
                $this->out($video_id.' - '.$new_title);
            }
        }
        $this->out($count.' titles updated');
    }

}
?>
