<?php
/**
 * Short description for audio.php
 *
 * Long description for audio.php
 *
 * PHP versions 4 and 5
 *
 */
/**
 * Audio class
 *
 * @uses          AppModel
 * @package       mi-base
 * @subpackage    mi-base.app.models
 */
use Aws\Common\Aws;

class Audio extends AppModel {
/**
 * name property
 *
 * @var string 'Audio'
 * @access public
 */
	var $name = 'Audio';
/**
 * useTable property
 *
 * @var string 'audio'
 * @access public
 */
	var $useTable = 'audio';
/**
 * displayField property
 *
 * @var string 'description'
 * @access public
 */
	var $displayField = 'description';

	public function copyToS3($audio_id) {
        $audio = $this->find('first', array(
            'conditions' => array('Audio.id' => $audio_id),
            'contain' => array()
        ));
        // set model to lowercase
        $tmpfile = TMP.'uploads'.DS.$audio['Audio']['dir'].$audio['Audio']['filename'];
        $key = "{$audio['Audio']['client_id']}/audio/{$audio['Audio']['filename']}";
        $s3 = Aws::factory(AWS_CONFIG)->get('S3');
        $status = array();
        $s3->registerStreamWrapper();

        if(!file_exists("s3://".VMS_ARCHIVE."/{$key}")) {
            $s3->putObject(array(
                'Bucket' => "archive.fordela.com",
                'Key' => $key,
                'SourceFile' => $tmpfile,
                'ServerSideEncryption' => 'AES256'
            ));
        }
        
        if(file_exists("s3://".VMS_ARCHIVE."/{$key}")) {
        	$status['status'] = 'Finished';
			$status['description'] = 'Audio Moved to S3';
			$status['finished'] = date('Y-m-d H:i:s');

			$this->id = $audio['Audio']['id'];
			$this->set(array(
				'location' => "s3://".VMS_ARCHIVE."/{$key}"
			));
			$this->save();

			// Remove temporary Audio from recorder
			unlink($tmpfile);
        } else {
        	$status['status'] = 'Error';
			$status['description'] = 'There was an error moving the Audio to S3';
        }

        return $status;
    }

}
?>