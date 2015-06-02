<?php
App::uses('HttpSocket','Network/Http');
App::uses('Sanitize','Utility');
App::uses('ComponentCollection','Controller');
App::uses('AwsComponent','Controller/Component');

class FileMoverShell extends Shell {

    var $s3 = null;
    var $status = array();

  public $uses = array('JobQueue','Video','Audio','VideoVersion','Client');

    public function perform() {
        $this->initialize();
        $this->{array_shift($this->args)}();
    }

    // Make AwsComponent callable
    protected function _initAws() {
        $Collection = new ComponentCollection();
        return $this->Aws = new AwsComponent($Collection);
    }

  /**
  * See http://docs.amazonwebservices.com/AWSSDKforPHP/latest/#m=AmazonS3/create_mpu_object
  */
  public function copyToS3() {
      $src = $this->args[0];
      $dest = $this->args[1];
      $this->client_id = $this->args[2];
      $this->bucket = $this->args[3];
      $this->type = $this->args[4];
      $this->record_id = $this->args[5];
      $this->filename = $this->args[6];
      $this->source = (isset($this->args[7]) && $this->args[7] == true) ? true : false;
      $this->uploader = $this->args[8];
      // If TRUE, kick off encoding jobs
      $this->transcode = (isset($this->args[9]) && $this->args[9] == true) ? true : false;
      // Is Src ProRes?
      $this->prores = false;
      if($this->transcode == true) {
            // TODO: Update to save all mediainfo data returned
            $mediainfo = $this->Video->mediainfo($src);
            $this->prores = ($mediainfo['format'] == 'ProRes') ? true : false;
      }
      $this->log($this->args,'FileMoverArgs');

        $this->_initAws();
      try {
            if($this->Aws->mpu($src,$this->bucket,$dest)) {
                $this->_checkOnS3($src,$dest);
            } else {
                $this->status['status'] = 'Error';
                $this->status['description'] = 'File Failed to Make it to S3';
            }
      } catch (Exception $e) {
      $this->status['status'] = 'Error';
      $this->status['description'] = 'File Failed to Make it to S3::'.$e;
      }
      $jobId = end($this->args);
      $this->JobQueue->updateJob($jobId,$this->status);
  }

  public function renameOnS3(){
      $src = $this->args[0];
      $dest = $this->args[1];
      $this->client_id = $this->args[2];
      $this->bucket = $this->args[3];
      $this->type = $this->args[4];
      $this->record_id = $this->args[5];
      $this->filename = $this->args[6];
      $this->source = (isset($this->args[7]) && $this->args[7] == true) ? true : false;
      $this->log($this->args,'FileMoverArgs');

      $this->_initAws();

      try {
            if($this->Aws->get('S3','copyObject',array('Bucket' => $this->bucket,'Key' => $dest,'CopySource' => $this->bucket.'/'.$src,'ServerSideEncryption' => 'AES256'))) {
                $this->_checkOnS3($src,$dest,1);
                if($this->status['status'] == 'Finished'){
                    $this->Aws->get('S3','deleteObject',array('Bucket' => $this->bucket,'Key' => $src));
                }
            } else {
                $this->status['status'] = 'Error';
                $this->status['description'] = 'File Failed to Move';
            }
      } catch (Exception $e) {
      $this->status['status'] = 'Error';
      $this->status['description'] = 'File Failed to Move::'.$e;
      }
      $jobId = end(array_values($this->args));
      $this->JobQueue->updateJob($jobId,$this->status);
  }

  private function _checkOnS3($src,$dest,$rename = 0) {
        $this->_initAws();
        $this->Aws->registerStreamWrapper();
        $this->dest_filesize = filesize('s3://'.$this->bucket.'/'.$dest);
        if($rename) {
            $src_filesize = filesize('s3://'.$this->bucket.'/'.$src);
        } else {
            $src_filesize = filesize($src);
        }

      if($this->dest_filesize == $src_filesize){
          $this->filesize = $src_filesize;
          $this->status['status'] = 'Finished';
          $this->status['description'] = 'File Moved and Size Matches';
          $this->status['finished'] = date('Y-m-d H:i:s');

          //TODO: Add attempt to delete source file off of uploader here
          $this->_deleteFromUploader($src);
          $this->_update_record($rename);
      } else {
          $this->status['status'] = 'Error';
          $this->status['description'] = 'File Moved but Size Does Not Match';
      }
  }

  private function _deleteFromUploader($src) {
      $this->log('Yes, we are getting to the function','FileMoverArgs');
      return is_file($src)? @unlink($src): $this->log('Not a file - '.$src, 'FileMoverArgs');
  }

  private function _update_record($rename=0){

    switch($this->type){
        case 'Audio': $path = '/'.$this->client_id.'/audio/'; break;
        case 'SideBySide':
        $this->is3d = true;
        $this->type = 'Video';
        $path = '/'.$this->client_id.'/videos/';
        break;
        case 'Video': $path = '/'.$this->client_id.'/videos/'; break;
        default: return; // skip if not Audio or Video
    }
    $this->{$this->type}->id = $this->record_id;

    //If video saving to archive update source version
    if($this->type == 'Video' && (strpos($this->bucket,'archive') !== false)){
        $video_version = $this->VideoVersion->find('first',array('conditions'=>array('video_id'=>$this->record_id,'name'=>'Source')));
        $video_version['VideoVersion']['host'] = 'http://'.$this->bucket.'/';
        $video_version['VideoVersion']['dir'] = $this->client_id.'/videos/';
        $this->VideoVersion->save($video_version);

        $this->{$this->type}->saveField('location','s3://'.$this->bucket.$path.$this->filename);
    }

    //Save To Media
    if($this->type == 'Video' && (strpos($this->bucket,'media') !== false) && $this->source == true){

        $db = $this->VideoVersion->getDataSource();
        $this->VideoVersion->updateAll(
            array( // fields
                'VideoVersion.filesize' => $this->dest_filesize,
                'VideoVersion.host' => $db->value('http://'.$this->bucket.'/','string'),
                'VideoVersion.dir' => $db->value($this->client_id.'/','string'),
                'VideoVersion.filename' => $db->value($this->filename,'string')
            ),
            array( // conditions
                'VideoVersion.video_id' => $this->record_id,
                'VideoVersion.name' => 'Source'
            )
        );

        // Is this necessary? Or does it actually work?
        if($rename){
            $video_version['VideoVersion']['filename'] = $this->filename;
            $prime_version = $this->VideoVersion->find('first',array('conditions'=>array('video_id'=>$this->record_id,'name'=>'Primary')));
            $this->VideoVersion->id = $prime_version['VideoVersion']['id'];
            $this->VideoVersion->savefield('filename', $this->filename);
            // TODO: Is this used? It looks incorrect.
            //$this->VideoVersion->id =null; // Should be: $this->Video->id = $this->record_id;
            //$this->Video->savefield('archive', $this->filename);
        }

        $path = '/'.$this->client_id.'/'; // Media bucket path different from Archive

        $video = array(
            'active' => 1,
            'archive' => $this->filename,
            'location' => 's3://'.$this->bucket.$path.$this->filename,
            'archive_dir' => $this->client_id.'/',
            'filesize' => $this->filesize,
            'image1' => 'video_default_screenshot.jpg',
            'image_key' => 'videos/video_default_screenshot.jpg'
        );

        $this->Video->set($video);
        $this->Video->save();
    }

        //Update Audio Record
        if($this->type == 'Audio'){
            $this->Audio->set(array(
                'location' => 's3://'.$this->bucket.$path.$this->filename,
                'archive_dir' => $this->client_id.'/'
            ));
            $this->Audio->save();
        }

    if($this->transcode == true){
        // POST back to VMS to kick off creating jobs
        App::uses('HttpSocket', 'Network/Http');
        $HttpSocket = new HttpSocket();

        $this->Video->set(array(
          'image1' => 'encoding.jpg',
          'image_key' => 'videos/encoding.jpg'
        ));
        $this->Video->save();

        // If $this->type was SideBySide it was overwritten. Use $this->is3d instead.
        $is3d = (isset($this->is3d)) ? $this->is3d : 0;

        $data = array('Video' => array(
          'client_id' => $this->client_id,
          'id' => $this->record_id, // video_id
          '3d' => $is3d,
          'prores' => $this->prores
        ));

        $results = $HttpSocket->post(ENVIRONMENT_APP_URL.'/videos/create_jobs', $data);
    }

    // Flag video record prores
    if($this->type == 'Video' && $this->prores == true){
        $this->Video->savefield('prores',1);
    }
  }

  /**
  *  Called after being uploaded via FTP Client
  *  Format:  cake file_mover client_ftp CLIENT_ID FILE1 FILE2
  *  Example: cake file_mover client_ftp 995 video1.mov my%20file.mp4
  */
  public function client_ftp(){
    // Arguments client_id, filename(s)
    $this->log($this->args, 'clientFtpArgs');

    $client_id = array_shift($this->args);
    $this->Client->contain('PrimaryUser');
    $client = $this->Client->findById($client_id,array('Client.default_video_status','Client.default_encode','PrimaryUser.id'));

    $this->_initAws();
        $this->Aws->registerStreamWrapper();

    // Get the args in the form of a string
    $filename = implode(' ',$this->args);

    // Get the title without the extension
    $ext_loc = strrpos($filename,'.');
    $title = substr($filename, 0, $ext_loc);

    // Sanitize $filename to prevent possible errors
    $newfilename = Sanitize::paranoid($filename, array('.','_'));

    $this->out('Filename: '. $filename);
    $this->out('Title: '.$title);
    $this->out('New Filename: '.$newfilename);

    $src = TMP.'uploads'.DS.$client_id.DS.'ftp'.DS.'incoming'.DS.$filename;
    $dest = TMP.'uploads'.DS.$client_id.DS.'videos'.DS.$newfilename;

    // Verify file has not already been copied
    $onArchive = file_exists('s3://archive.fordela.com/'.$client_id.'/videos/'.$newfilename);
    $onMedia = file_exists('s3://media.fordela.com/'.$client_id.'/'.$newfilename);

    if(!is_file($dest) && !$onArchive && !$onMedia) {
            $this->log('ClientID: '.$client_id.' File: '.$newfilename.' - Process the file!', 'clientFtpArgs');

        // Copy file using sanitized filename to CLIENT_ID/videos
        if(copy($src, $dest)) {
        $filesize = filesize($dest);
        // Create Video and Source records
        $video = $this->_create_video_record($client_id, $client, $title, $newfilename, $filesize);
        $source = $this->_create_source_record($video);

        $HttpSocket = new HttpSocket();
        $data = array(
          'client_id' => $client_id,
          'video_id' => $video['id'],
          'filename' => $video['archive'],
          'user_id' => $client['PrimaryUser']['id'],
          'transcode' => $client['Client']['default_encode'],
          'uploader' => 'ClientFTP'
        );
        // Create jobqueue job to move to s3
        $results = $HttpSocket->post(ENVIRONMENT_APP_URL.'/videos/node_uploader_notification', $data);
        $this->log($results['body'], 'clientFtpArgs');
        } else {
            $this->log('ClientID: '.$client_id.' '.$filename.' -> '.$newfilename.' - Failed to copy file', 'clientFtpArgs');
        }
    }else{
            $this->log('ClientID: '.$client_id.' '.$filename.' -> '.$newfilename.' - Already copied / processed', 'clientFtpArgs');
    }

  }

  protected function _create_video_record($client_id, $client, $title, $filename, $filesize) {
    $this->Video->create();
    //IF default_encode is TRUE set active to FALSE and image1 to processing.jpg
    $active = ($client['Client']['default_encode'] == 1) ? 0 : 1;
    $image1 = ($client['Client']['default_encode'] == 1) ? 'processing.jpg' : 'video_default_screenshot.jpg';
    $video = array(
          'client_id' => $client_id,
          'title' => $title,
          'active' => $active,
          'public' => $client['Client']['default_video_status'],
          'archive' => $filename,
          //Uploader definition?
          'location' => VIDEO_UPLOAD_URL.'/'.$client_id.'/videos/'.$filename,
          'archive_dir' => $client_id.'/videos/',
          //'fps' => $fps,
          'filesize' => $filesize,
          //'runtime' => $runtime,
          //'width' => $width,
          //'height' => $height,
          'image1' => $image1,
          'created_by' => $client['PrimaryUser']['id']
        );
    if($this->Video->save($video)){
      $msg = $filename.' - Success! Video record created';
      $this->out($msg);
      $this->log($msg, 'clientFtpArgs');
      $video['id'] = $this->Video->id;
    }else{
      $msg = $filename.' - failed to save Video record';
      $this->out($msg);
      $this->log($msg, 'clientFtpArgs');
    }

    return $video;
  }

  protected function _create_source_record($video){

    $info = pathinfo($video['archive']);

    $this->VideoVersion->create();
    $source = array(
          'video_id'=>$video['id'],
          'encoding_profile_id' => 0,
          'name' => 'Source',
          'filename' => $video['archive'],
          'ext' => $info['extension'],
          'host' => VIDEO_UPLOAD_URL.'/'.$video['client_id'].'/videos/'.$video['archive'],
          'dir' => $video['id'].'/videos/',
          'format' => $info['extension'],
          'filesize' => $video['filesize'],
          'status' => 'Received'
        );
    if($this->VideoVersion->save($source)){
      $msg = $video['archive'].' - Success! Version Source record created';
      $this->out($msg);
      $this->log($msg, 'clientFtpArgs');
      $source['id'] = $this->VideoVersion->id;
    }else{
      $msg = $video['archive'].' - Failed to save Version Source record';
      $this->out($msg);
      $this->log($msg, 'clientFtpArgs');
    }

    return $source;
  }

    /**
     * Initially gets queued from the encoder notifications controller if definition
     * COPY_TO_PUBMEDIA is defined.
     *
     * Will copy a version from bucket (archive or media) to bucket
     * pubmedia.fordela.com using the same key
     */
    public function copyVersionPubmedia() {
        $src_bucket = $this->args[0];
        $key = $this->args[1];
        $this->_initAws();
        $this->Aws->get('S3','copyObject',array(
            'CopySource' => $src_bucket.'/'.$key,
            'Bucket' => 'pubmedia.fordela.com',
      'Key' => $key
        ));
        $this->Aws->registerStreamWrapper();
        if (!file_exists('s3://pubmedia.fordela.com/'.$key)) {
            $this->status['status'] = 'Error';
            $this->status['description'] = 'Failed to copy Version to pubmedia.fordela.com';
            $this->status['finished'] = date('Y-m-d H:i:s');
        } else {
            $this->status['status'] = 'Finished';
            $this->status['description'] = 'Version copied to pubmedia.fordela.com';
            $this->status['finished'] = date('Y-m-d H:i:s');
        }
        $jobId = end(array_values($this->args));
        $this->JobQueue->updateJob($jobId,$this->status);
    }
}
?>
