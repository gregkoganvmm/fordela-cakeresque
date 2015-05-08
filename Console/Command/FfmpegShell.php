<?php
/**
* This shell is for pulling video down from S3 and then creating
* screenshots
*/
use Aws\Common\Aws;
App::uses('HttpSocket', 'Network/Http');
App::uses('Folder','Utility');

class FfmpegShell extends Shell {

	public $status = array();

	var $uses = array('JobQueue','Video','SearchIndex');

	public function perform() {
        $this->initialize();
        $this->{array_shift($this->args)}();
    }

	public function trim() {
		// Set default variables
		$video_id = $this->args[0];
		$user_id = $this->args[1];
		$point_in = $this->args[2];
		$point_out = $this->args[3];
		$video = $this->Video->findById($video_id,array('id','client_id','location','archive','archive_dir','image1','thumb_count','filesize','created'));
		$this->get_file($video); // Download the file to recorder
		$filename = $this->_ffmpegTrimFile($video,$point_in,$point_out);
		$this->out('Process trimmed video by POSTing back to /videos/notify_jobqueue');
		$this->_processVideo($video['Video']['client_id'],$user_id,$filename,1,$user_id);
		$this->status = array(
			'status' => 'Finished',
			'description' => 'Trimmed video created',
			'finished' => date('Y-m-d H:i:s')
		);
		$jobId = end(array_values($this->args));
		$this->JobQueue->updateJob($jobId,$this->status);
	}

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

	protected function _ffmpegTrimFile($video,$point_in,$point_out) {
		$this->out('Attempting to create a trimmed video from Video ID: '.$video['Video']['id']);
		$source = TMP.'uploads'.DS.$video['Video']['client_id'].DS.'videos'.DS.$video['Video']['archive'];
		$info = pathinfo($video['Video']['location']);
		$newfile = $info['filename'].'_trimmed_'.TIME().'.'.$info['extension'];
		$newfilepath = TMP.'uploads'.DS.$video['Video']['client_id'].DS.'videos'.DS.$newfile;
		exec("ffmpeg -i {$source} -vcodec copy -acodec copy -ss {$point_in} -t {$point_out} {$newfilepath}");
		$this->out('Attempted video trim complete');
		unlink($source); // clean up - delete source video off of recorder
		return $newfile;
	}

    public function get_file($video = array()) {
    	$this->out('Attempting to pull file from S3');
		if(empty($video)){
			$video = $this->Video->findById($this->args[0],array('id','client_id','location','archive','archive_dir','image1','thumb_count','filesize','created'));
		}
		if(empty($video)){
			$this->out('No result for Video ID: '.$this->args[0]);
			die;
		}
		$this->out('File: '.$video['Video']['archive'].' - Size: '.$video['Video']['filesize']);
		// Determine bucket
		$pathinfo = pathinfo($video['Video']['location']);
		$pathinfo['dirname'] = str_replace('s3://', '', $pathinfo['dirname']);
		$parts = explode('.',$pathinfo['dirname']);
		$bucket = ($parts[0] == 'archive' || $parts[0] == 'archive-dev') ? VMS_ARCHIVE : VMS_MEDIA;
		$video_file = TMP.'uploads'.DS.$video['Video']['client_id'].DS.'videos'.DS.$video['Video']['archive'];
		if(!file_exists($video_file)){
			$this->out('File not present, pulling from S3');
			$s3 = Aws::factory(AWS_CONFIG)->get('S3');
			$s3->getObject(array(
	            'Bucket' => $bucket,
	            'Key' => $video['Video']['archive_dir'].$video['Video']['archive'],
	            'SaveAs' => $video_file
	        ));
        }
        $exists = (!file_exists($video_file)) ? 'Error! File failed to download from S3' : 'Success! File was downloaded from S3';
        $this->out($exists);
    }

    /* TODO:
    * 1) Determine bucket
    */

	public function screenshot() {
		// $this->args[0] = $video_id
		if(count($this->args) < 1){
			$this->out('Missing Video ID!');
			die;
		}
		$jobId = end(array_values($this->args));
		$video = $this->Video->find('first',array(
			'conditions' => array('Video.id' => $this->args[0]),
			'fields' => array('Video.id','Video.client_id','Video.location','Video.archive','Video.archive_dir','Video.image1','Video.thumb_count','Video.filesize','Video.created')
		));
		if(empty($video)){
			$this->out('No result for Video ID: '.$this->args[0]);
			die;
		}
		// Set default at 00:00:10 if no argument passed
		$timecode = (isset($this->args[1])) ? $this->args[1] : '00:00:10';
		$this->out('Timecode: '.$timecode);

		// Determine bucket
		$pathinfo = pathinfo($video['Video']['location']);
		$pathinfo['dirname'] = str_replace('s3://', '', $pathinfo['dirname']);
		$parts = explode('.',$pathinfo['dirname']);

		$bucket = ($parts[0] == 'archive' || $parts[0] == 'archive-dev') ? VMS_ARCHIVE : VMS_MEDIA;

		// 1) Pull file down from S3
		$video_file = TMP.'uploads'.DS.$video['Video']['client_id'].DS.'videos'.DS.$video['Video']['archive'];
		if(!file_exists($video_file)){
			$this->out('File not present, pulling from S3');
			$s3 = Aws::factory(AWS_CONFIG)->get('S3');
			$s3->getObject(array(
	            'Bucket' => $bucket,
	            'Key' => $video['Video']['archive_dir'].$video['Video']['archive'],
	            'SaveAs' => $video_file
	        ));
        }

        // 2) Use ffmpeg to create screenshot on image server
        if(!file_exists($video_file) || filesize($video_file) < 1){
        	$this->status['status'] = 'Error';
			$this->status['description'] = 'Error: File was NOT pulled from S3. Does it exist?';
			$this->status['finished'] = date('Y-m-d H:i:s');
			$this->JobQueue->updateJob($jobId,$this->status);
			// Remove empty or 0 byte file
			unlink($video_file);
        	die;
        }else{
        	$image = $video['Video']['client_id'].'_'.$video['Video']['id'].'_'.time().'-1.png';
        	$image_server = TMP.'video_thumbs'.DS.'img'.DS.'videos'.DS;
			$image_uploads = TMP.'uploads'.DS.'img'.DS.'videos'.DS.$video['Video']['id'].DS;
			$imgUploadsFolder = new Folder($image_uploads,true,0775);
        	exec("ffmpeg -i " . $video_file  . " -ss " . $timecode . " -r 1 -an -vframes 1 -f mjpeg " . $image_uploads.$image);
			if(!file_exists($image_uploads.$image)) {
				// Try a second attempt if it failed
				exec("ffmpeg -i " . $video_file  . " -ss " . $timecode . " -r 1 -an -vframes 1 -f mjpeg " . $image_uploads.$image);
			}
			if(file_exists($image_uploads.$image)) {
				// TODO: Temporary copy to image-server until it goes away
				copy($image_uploads.$image,$image_server.$image);
				// Create AssetImage record
				$this->AssetImage = ClassRegistry::init('AssetImage');
				$this->AssetImage->addImage($image_uploads.$image,'Video',$video['Video']['id']);
				// Generate thumbs and move them to S3
				$this->_generateThumbs($video['Video']['id'],$image);
				$this->_moveToS3($video['Video']['id'],$image);
			}
        }

		// 3) All good so far -> Update video record
		$this->Video->id = $video['Video']['id'];
		$this->Video->set(array('image1' => $image,'thumb_count' => 1,'image_key' =>'videos/'.$video['Video']['id'].'/'.$image));
		$this->Video->save();

		try {
			$index = $this->SearchIndex->find('first',array(
				'conditions' => array('SearchIndex.model' => 'Video','SearchIndex.foreign_key' => $video['Video']['id']),
				'fields' => array('SearchIndex.id')
			));
			if(!empty($index['SearchIndex']['id'])){
				$this->SearchIndex->id = $index['SearchIndex']['id'];
				$this->SearchIndex->savefield('thumbnail',$image);
			}
		} catch(Exception $e){
			// Do nothing
		}

		// 4) clean up and remove video from recorder
		unlink($video_file);

		// 5) Update the job status
		$this->status['status'] = 'Finished';
		$this->status['description'] = 'Screenshot generated';
		$this->status['finished'] = date('Y-m-d H:i:s');
		$this->JobQueue->updateJob($jobId,$this->status);
	}

    protected function _generateThumbs($video_id,$image) {
        App::import('Vendor', 'SmartImage', array('file' => 'SmartImage.class.php'));
        $sizes = array('320X240','120X76','80X45','120X76');
        $image_path = TMP.'uploads'.DS.'img'.DS.'videos'.DS.$video_id.DS.$image;
        $src = basename($image_path);
        $dir = new Folder(TMP.'uploads'.DS.'img'.DS.'x'.DS.'videos'.DS.$video_id, true, 0755);
        foreach($sizes as $k => $size) {
            $s = explode('X',$size);
            $width = $s[0];
            $height = $s[1];
            $cut = false;
            $img = new SmartImage($image_path);
            if($k <> 3 && !file_exists(TMP.'uploads'.DS.'img'.DS.'x'.DS.'videos'.DS.$video_id.DS.$width.'X'.$height.'_'.$src)) {
                $img->resize($width, $height, $cut);
                $img->saveImage(TMP.'uploads'.DS.'img'.DS.'x'.DS.'videos'.DS.$video_id.DS.$width.'X'.$height.'_'.$src, 85);
            }
            if($k == 3 && !file_exists(TMP.'uploads'.DS.'img'.DS.'x'.DS.'videos'.DS.$video_id.DS.'120X76_cut_'.$src)) {
                $cut = true;
                $img->resize($width, $height, $cut);
                $img->saveImage(TMP.'uploads'.DS.'img'.DS.'x'.DS.'videos'.DS.$video_id.DS.'120X76_cut_'.$src, 85);
            }
            //$img->printImage();
            $img->close();
        }
        return;
    }

	protected function _moveToS3($video_id,$image) {
        $s3 = Aws::factory(APP.'Config'.DS.'fordela-aws-oregon-config.php')->get('S3');
		$s3->putObject(array(
		    'Bucket'     => VMS_IMAGES,
		    'Key'        => 'videos/'.$video_id.'/'.$image,
		    'SourceFile' => TMP.'uploads'.DS.'img'.DS.'videos'.DS.$video_id.DS.$image,
			'ACL' => 'public-read'
		));

        $prefix = 'x'.DS.'videos'.DS.$video_id;
        $dir = TMP.'uploads'.DS.'img'.DS.$prefix;
        return $s3->uploadDirectory($dir, VMS_IMAGES, $prefix, array(
            'params'      => array('ACL' => 'public-read'),
            'concurrency' => 20,
            'debug'       => true
        ));
    }
}
?>
