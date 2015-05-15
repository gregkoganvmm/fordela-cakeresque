<?php
/**
* General Shell for odd or random jobs
*
*/
App::uses('HttpSocket','Network/Http');
App::uses('ComponentCollection','Controller');
App::uses('AwsComponent','Controller/Component');
App::import('Controller','Notifications.Send');

class RandomTaskShell extends Shell {

	var $status = array();

	var $uses = array('JobQueue','Video','User');

	public function perform()
    {
        $this->initialize();
        $this->{array_shift($this->args)}();
    } 

	/**
	 * From VMS this is added to JobQueue from the LoginListener event
	 *
	 */
	public function login_notification() {
		$email = $this->args[0];
		$user_id = $this->args[1];
		$client_id = $this->args[2];

		$this->Send = new SendController();
		// returns id, username, name
		$user = $this->Send->notify_login($email,$user_id, $client_id);

		// Update JobID
		$this->status['status'] = 'Finished';
		$this->status['description'] = 'Notify '.$email.' that a user logged in. Username: '.$user['User']['username'];
		$this->status['finished'] = date('Y-m-d H:i:s');
		$jobId = end(array_values($this->args));
		$this->JobQueue->updateJob($jobId,$this->status);
	}

	// Make AwsComponent callable
	protected function _initAws() {
		$Collection = new ComponentCollection();
		return $this->Aws = new AwsComponent($Collection);
	}

    public function s3ToRecorder() {
		if(
			empty($this->args[0]) ||
			empty($this->args[1]) ||
			empty($this->args[2])
		) {
			$this->out('Invalid argument(s) error!');
			die;
		}
        $bucket = $this->args[0];
        $client_id = $this->args[1];
		$filename = $this->args[2];

		$key = $client_id.'/'.$filename;
		$exists = $this->_downloadFromS3($bucket,$client_id,$filename,$key);
		$this->out($filename.' '.$exists);

		$this->out('Downloads to recorder complete');
    }

    /**
    * Run via command line
    *
    * Console/cake random_tast mediainfo VIDEO_ID
    */
    public function mediainfo() {
    	if(!$this->args[0]) {
    		$this->out('Missing VideoID');die;
    	}
    	$video_id = $this->args[0];
    	$video = $this->Video->find('first',array(
    		'conditions' => array('Video.id' => $video_id),
    		'fields' => array(
    			'Video.id',
    			'Video.client_id',
    			'Video.archive',
    			'Video.archive_dir',
    			'Video.location',
    			'Video.width',
    			'Video.height',
    			'Video.fps'
    		)
    	));
    	if (!$video['Video']['id']) {
    		$this->out('Invalid VideoID');die;
    	}

		$filename = preg_replace('/[^a-z\d _.]/i', '', $video['Video']['archive']);
		$filename = str_replace(' ','_',$filename);

    	$video_file = TMP.'uploads'.DS.$video['Video']['client_id'].DS.'videos'.DS.$filename;

    	if(!file_exists($video_file)) {
    		// Get bucket - Assume archive for now
    		$bucket = 'archive.fordela.com';
    		$key = $video['Video']['archive_dir'].$video['Video']['archive'];
				$this->_initAws();
				$this->Aws->registerStreamWrapper();
				if(file_exists('s3://'.$bucket.'/'.$key) && filesize('s3://'.$bucket.'/'.$key) > 0){
    			$this->_downloadFromS3($bucket,$video['Video']['client_id'],$filename,$key);
				} else {
					$this->out('File not on S3');die;
				}
    	}

    	try{
    		$mediainfo = $this->Video->mediainfo($video_file);
    		debug($mediainfo);

        $this->Video->id = $video_id;
        $update = array(
            'runtime' => $mediainfo['runtime'],
            'width' => $mediainfo['width'],
            'height' => $mediainfo['height'],
            'fps' => $mediainfo['fps'],
            'filesize' => $mediainfo['filesize']
        );
        $this->Video->set($update);
        if($this->Video->save($update)) {
            $this->out('*****Mediainfo updated!*****');
            unlink($video_file);
        }
				// If second arg is true mark the job Finished as the job is using a worker
				if(isset($this->args[1])) {
					$this->status['status'] = 'Finished';
					$this->status['description'] = 'MediaInfo complete for VideoID: '.$this->args[0];
					$this->status['finished'] = date('Y-m-d H:i:s');
					$jobId = end(array_values($this->args));
					$this->JobQueue->updateJob($jobId,$this->status);
				}
    	} catch(Exception $e){
    		$this->out('Error!');
            echo $e->getMessage();
    	}
    	$this->out('End of script');
    }

    protected function _downloadFromS3($bucket,$client_id,$filename,$key) {
        // Get file and put it on Recorder
        $video_file = TMP.'uploads'.DS.$client_id.DS.'videos'.DS.$filename;
				$this->_initAws();
        $this->out('File not present, pulling from S3');
				$this->Aws->get('S3','getObject',array(
						'Bucket' => $bucket,
						'Key' => $key,
						'SaveAs' => $video_file
				));
        $exists = (!file_exists($video_file)) ? false : true;

        return $exists;
    }


	/**
	* Pre Cache's a clients analytics queries to speed up initial
	* page loads
	*
	* ClientID = $this->args[0]
	*/
	public function preCacheAnalytics() {
		$HttpSocket = new HttpSocket();
		$data = array('client_id' => $this->args[0]);
		$response = $HttpSocket->post(ENVIRONMENT_APP_URL."/analytics/analytics/preCache",$data);
		$this->log(ENVIRONMENT_APP_URL."/analytics/analytics/preCache",'request');
		$this->log($response,'request');
		$this->status['status'] = 'Finished';
		$this->status['description'] = 'Analytics preCache complete for Client ID: '.$this->args[0];
		$this->status['finished'] = date('Y-m-d H:i:s');
		$jobId = end(array_values($this->args));
		$this->JobQueue->updateJob($jobId,$this->status);
	}


    /**
	* Checks the stats cache file
	*/
	public function checkStat() {
		$HttpSocket = new HttpSocket();
		$data = array('model' => $this->args[0], 'type' => $this->args[1]);
		$response = $HttpSocket->post(ENVIRONMENT_APP_URL."/stats/check_update_stat/".$this->args[0].'/'.$this->args[1]);
		$this->status['status'] = 'Finished';
		$this->status['description'] = 'Updated stats file';
		$this->status['finished'] = date('Y-m-d H:i:s');
		$jobId = end(array_values($this->args));
		$this->JobQueue->updateJob($jobId,$this->status);
	}
}
?>
