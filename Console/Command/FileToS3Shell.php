<?php
/**
* New Shell for moving files to S3 using AWS SDK for PHP 2
*
*/
use Aws\Common\Aws;
App::uses('HttpSocket', 'Network/Http');
App::uses('Folder', 'Utility');
App::uses('File', 'Utility');

class FileToS3Shell extends Shell {

    public $status = array();

    public $uses = array(
        'Audio',
        'Attachment',
        //'Client',
        'JobQueue',
        //'Video',
        //'VideoVersion'
    );

    public function perform() {
        $this->initialize();
        $this->{array_shift($this->args)}();
    }

    /*public function test()
    {
        $this->out('TODO: Attempt to download file via stream to get around memory issues with large files');
        $this->out('Attempting download...');

        //////////////////////////////////////////////////////////////////////////////////
        // TODO: Use code below and attempt to update function clientS3Bucket() with this
        // instead of getObject
        //////////////////////////////////////////////////////////////////////////////////

        $dir = new Folder(TMP.'downloads/995/videos',true,0755);
        $s3 = Aws::factory(AWS_CONFIG)->get('S3');
        $s3->registerStreamWrapper();
        // Open new file and write to it from stream
        $output = fopen(TMP.'downloads/995/videos/myfile.mp4','w');
        if ($stream = fopen('s3://media.fordela.com/995/10549_700_1335906750.mp4', 'r')) {
            while (!feof($stream)) {
                fwrite($output, fread($stream, 1024));
            }
            fclose($stream);
        }
        $this->out('Finished');
    }*/

    public function audioToS3() {
        $audio_id = $this->args[0];
        $this->status = $this->Audio->copyToS3($audio_id);
        $jobId = end($this->args);
        $this->JobQueue->updateJob($jobId,$this->status);
    }

    public function attachmentToS3() {
        $attachment_id = $this->args[0];
        $this->status = $this->Attachment->copyToS3($attachment_id);
        $jobId = end($this->args);
        $this->JobQueue->updateJob($jobId,$this->status);
    }

    /**
    * Get the audio from Dropbox then process it like normal
    */
    public function dropbox_audio() {
        $this->log($this->args,'dropbox');
        $client_id = $this->args[0];
        $user_id = $this->args[1];
        $url = $this->args[2];
        $filename = $this->args[3];
        $jobId = end($this->args);
        $this->http = new HttpSocket();
        $this->_downloadFromDropbox($client_id,$url,$filename,'audio');
        $this->_processAudio($client_id,$user_id,$filename);
        $this->status = array(
            'status' => 'Finished',
            'description' => 'Dropbox audio downloaded and processing started',
            'finished' => date('Y-m-d H:i:s')
        );
        $this->JobQueue->updateJob($jobId,$this->status);
    }

    // TODO: Move this to Audio model
    protected function _processAudio($client_id,$user_id,$filename) {
        $file = new File(TMP.'uploads'.DS.$client_id.DS.'audio'.DS.$filename);
        $info = $file->info();

        $data = array(
            'client_id' => $client_id,
            'title' => $filename,
            'description' => '',
            'filename' => $filename,
            'user_id' => $user_id,
            'uploader' => 'Dropbox',
            'filesize' => $info['filesize'],
            'mimetype' => $info['mime'],
            'ext' => $info['extension']
        );
        return $this->http->post(ENVIRONMENT_APP_URL.'/audio/notify_jobqueue', $data);
    }

    /**
    * Get the video from Dropbox then process it like normal
    */
    public function box_platform() 
    {
        $this->log($this->args,'box_platform');
        $client_id = $this->args[0];
        $user_id = $this->args[1];
        $url = $this->args[2];
        $filename = $this->args[3];
        $transcode = ($this->args[4] == 1) ? 1 : 0;
        $jobId = end($this->args);
        $this->_downloadFromDropbox($client_id,$url,$filename);
        $this->log('Download from dropbox complete','box_platform');
        //clean and rename filename if necessary
        $newfilename = $this->cleanFilename($filename);
        // Delete new filename before running PHP rename to clear any statcache
        @unlink(TMP.'uploads'.DS.$client_id.DS.'videos'.DS.$newfilename);
        rename(TMP.'uploads'.DS.$client_id.DS.'videos'.DS.$filename,TMP.'uploads'.DS.$client_id.DS.'videos'.DS.$newfilename);
        $this->_processVideo($client_id,$user_id,$newfilename,$transcode,'Box Platform');
        $this->log('Post back to VMS','box_platform');
        $this->status = array(
            'status' => 'Finished',
            'description' => 'Box Platform video downloaded and processing started',
            'finished' => date('Y-m-d H:i:s')
        );
        $this->JobQueue->updateJob($jobId,$this->status);
    }

    /**
    * Get the video from Dropbox then process it like normal
    */
    public function dropbox() {
        $this->log($this->args,'dropbox');
        $client_id = $this->args[0];
        $user_id = $this->args[1];
        $url = $this->args[2];
        $filename = $this->args[3];
        $transcode = ($this->args[4] == 1) ? 1 : 0;
        $jobId = end($this->args);
        $this->_downloadFromDropbox($client_id,$url,$filename);
        $this->log('Download from dropbox complete','dropbox');
        //clean and rename filename if necessary
        $newfilename = $this->cleanFilename($filename);
        // Delete new filename before running PHP rename to clear any statcache
        @unlink(TMP.'uploads'.DS.$client_id.DS.'videos'.DS.$newfilename);
        rename(TMP.'uploads'.DS.$client_id.DS.'videos'.DS.$filename,TMP.'uploads'.DS.$client_id.DS.'videos'.DS.$newfilename);
        $this->_processVideo($client_id,$user_id,$newfilename,$transcode,'Dropbox');
        $this->log('Post back to VMS','dropbox');
        $this->status = array(
            'status' => 'Finished',
            'description' => 'Dropbox video downloaded and processing started',
            'finished' => date('Y-m-d H:i:s')
        );
        $this->JobQueue->updateJob($jobId,$this->status);
    }

    /**
     * Clean the filename to alphanumeric characters plus dashes, underscores, and periods
     */
    protected function cleanFilename($filename)
    {
        $fileInfo = pathinfo($filename);
        // Update filename to alphanumeric characters plus dashes, underscores, and periods
        $str = trim(preg_replace("/[^a-zA-Z0-9'\s-_.]/", '', $fileInfo['filename']));
        $str = str_replace([' '],'_',$str);

        // Strip off non alphanumeric characters from beginning of new filename
        $strArr = str_split($str);
        foreach($strArr as $char) {
            if (ctype_alnum($char)) {
                break;
            } else {
                $str = substr($str, 1);
            }
        }

        $newfilename = $str.'.'.$fileInfo['extension'];
        return $newfilename;
    }

    // TODO: Move this to Video model
    protected function _processVideo($client_id,$user_id,$filename,$transcode,$uploader) {
        $data = array(
            'client_id' => $client_id,
            'title' => $filename,
            'description' => '',
            'filename' => $filename,
            'user_id' => $user_id,
            'transcode' => $transcode,
            'uploader' => $uploader
        );
        $this->http = new HttpSocket();
        return $this->http->post(ENVIRONMENT_APP_URL.'/videos/notify_jobqueue', $data);
    }

    /**
    * Being used by both Video and Audio. Default is video unless $typepath passed is 'audio'
    *
    * See - http://book.cakephp.org/2.0/en/core-utility-libraries/httpsocket.html
    */
    protected function _downloadFromDropbox($client_id,$url,$filename, $typepath = 'videos') {
        $f = fopen(TMP.'uploads'.DS.$client_id.DS.$typepath.DS.$filename, 'w');
        $this->http = new HttpSocket();
        $this->http->setContentResource($f);
        $this->http->get($url);
        fclose($f);
        return;
    }

    /**
    * NEED: $user_id, $client_id, $filename, $transcode
    *
    *
    */
    public function clientS3Bucket() {
        $this->out('Attempting to pull file from S3');

        $bucket = $this->args[0];
        $key = $this->args[1];
        $client_id = $this->args[2];
        $user_id = $this->args[3];
        $transcode = ($this->args[4] == 1) ? 1 : 0;
        $jobId = end($this->args);

        $file_info = explode('/',$key);
        $filename = array_pop($file_info);
        $filename = preg_replace('/[^a-z\d _.]/i', '', $filename);
        $filename = str_replace(' ','_',$filename);

        $this->http = new HttpSocket();

        $exists = false;
        $attempt = 0;

        // Attempt to make folders in case they don't already exist
        $dir = new Folder(TMP.'uploads/'.$client_id.'/videos',true,0755);

        // Download file from S3 - retry a couple times if size mismatch
        while($attempt < 3 && $exists == false) {
          $video_file = TMP.'uploads'.DS.$client_id.DS.'videos'.DS.$filename;
          @unlink($video_file); // remove any partials before attempt
          $exists = $this->_downloadFromClientS3($bucket,$client_id,$filename,$key);
          $attempt++;
        }

        if($exists){
            $this->_processVideo($client_id,$user_id,$filename,$transcode,'Plupload S3');
            $this->status = array(
                'status' => 'Finished',
                'description' => 'Client S3 bucket video downloaded and processing started',
                'finished' => date('Y-m-d H:i:s')
            );
        }
        $this->JobQueue->updateJob($jobId,$this->status);
    }

    protected function _downloadFromClientS3($bucket,$client_id,$filename,$key) {
        // Set file paths for recorder and s3
        $video_file = TMP.'uploads'.DS.$client_id.DS.'videos'.DS.$filename;
        $video_s3 = 's3://'.$bucket.'/'.$key;

        $s3 = Aws::factory(AWS_CONFIG)->get('S3');
        $s3->registerStreamWrapper();

        // return true if job reset and file already on recorder
        if(file_exists($video_file) && filesize($video_file) > 0 && !is_file($video_s3)){
            return true;
        }

        // Normal first time through with no issues
        if(!file_exists($video_file) && file_exists($video_s3) && filesize($video_s3) > 0){
            $this->out('File not present, pulling from S3');
            $s3->getObject(array(
                'Bucket' => $bucket,
                'Key' => $key,
                'SaveAs' => $video_file
            ));

            sleep(3);

            // Delete bucket object if downloaded object filesizes match
            if(file_exists($video_file) && filesize($video_file) > 0) {
                // TODO: Need to figure out what to do if they don't match!
                if(filesize($video_file) == filesize($video_s3)) {
                    @unlink($video_s3);
                    // All looks good, return true to process the video
                    return true;
                }
            }
        }

        // Something is not right! Set status and return false to not process video
        $this->status = array(
            'status' => 'Error',
            'description' => 'Mismatch!',
            'finished' => date('Y-m-d H:i:s')
        );

        // recorder filesize 0
        if(filesize($video_file) == 0){
            $this->status['description'] = $this->status['description'].' Recorder: 0 byte';
        }
        // s3 filesize 0
        if(!is_file($video_s3)){
            $this->status['description'] = $this->status['description'].' S3: no file';
        }

        return false;
    }
}
?>
