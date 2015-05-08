<?php
/**
 * Short description for attachment.php
 *
 * Long description for attachment.php
 *
 * PHP versions 4 and 5
 *
 * Copyright (c) 2008, Andy Dawson
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright     Copyright (c) 2008, Andy Dawson
 * @link          www.ad7six.com
 * @package       mi-base
 * @subpackage    mi-base.app.models
 * @since         v 1.0
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 * Edits 2010, Daniel Scherr
 */
/**
 * Attachment class
 *
 * @uses          AppModel
 * @package       mi-base
 * @subpackage    mi-base.app.models
 */
use Aws\Common\Aws;

class Attachment extends AppModel {
/**
 * name property
 *
 * @var string 'Attachment'
 * @access public
 */
	var $name = 'Attachment';
/**
 * displayField property
 *
 * @var string 'description'
 * @access public
 */
	var $displayField = 'description';

/**
 * actsAs property
 *
 * @var array
 * @access public
 */
	var $actsAs = array(
		/*'ImageUpload' => array(
			'allowedMime' => '*',
			'allowedExt' => '*',
			'baseDir' => '{ROOT}{DS}app{DS}tmp{DS}uploads',
			'dirFormat' => '{$client_id}{DS}attachments{DS}{$model_type}{DS}{$foreign_id}',
			'overwriteExisting' => true,
			//'factoryMode' => true
		),*/
	);
/**
 * belongsTo property
 *
 * @var array
 * @access public
 */

	var $belongsTo = array(
		'Video' =>
		    array('className'  => 'Video',
		      'conditions' => '',
		      'order'      => '',
		      'foreignKey' => 'foreign_id',
		    ),
		'Playlist' =>
		    array('className'  => 'Playlist',
		      'conditions' => '',
		      'order'      => '',
		      'foreignKey' => 'foreign_id',
		    )
  );


  /**
   * Default Record for Demo Video
   *
   * @param int $video_id
   */
	function defaultVideoRecord($video_id,$client_id){
		$videoAttachment['Attachment']['user_id'] = 0;
		$videoAttachment['Attachment']['model_type'] = 'Video';
		$videoAttachment['Attachment']['client_id'] = $client_id;
		$videoAttachment['Attachment']['foreign_id'] = $video_id;
		$videoAttachment['Attachment']['name'] = 'About Fordela';
		$videoAttachment['Attachment']['filename'] = 'fordela_aboutus.pdf';
		$videoAttachment['Attachment']['ext'] = 'pdf';
		$videoAttachment['Attachment']['dir'] = 'demo';
		$videoAttachment['Attachment']['mimetype'] = 'application/pdf';
		$videoAttachment['Attachment']['filesize'] = 1080818;
		$videoAttachment['Attachment']['description'] = 'About Fordela';

		return $videoAttachment;
	}

	public function copyToS3($attachment_id) {
        $attachment = $this->find('first', array(
            'conditions' => array('Attachment.id' => $attachment_id),
            'fields' => array('Attachment.id','Attachment.model_type','Attachment.client_id','Attachment.foreign_id','Attachment.dir','Attachment.filename'),
            'contain' => array()
        ));
        // set model to lowercase
        $model = strtolower($attachment['Attachment']['model_type']);
        $tmpfile = TMP.'uploads'.DS.$attachment['Attachment']['dir'].DS.$attachment['Attachment']['filename'];
        $s3 = Aws::factory(AWS_CONFIG)->get('S3');
        $s3->putObject(array(
            'Bucket' => "archive.fordela.com",
            'Key' => "{$attachment['Attachment']['client_id']}/attachments/{$model}/{$attachment['Attachment']['foreign_id']}/{$attachment['Attachment']['filename']}",
            'SourceFile' => $tmpfile,
            'ServerSideEncryption' => 'AES256'
        ));
        $status = array();
        $s3->registerStreamWrapper();
        if(file_exists("s3://".VMS_ARCHIVE."/{$attachment['Attachment']['client_id']}/attachments/{$model}/{$attachment['Attachment']['foreign_id']}/{$attachment['Attachment']['filename']}")) {
        	$status['status'] = 'Finished';
			$status['description'] = 'Attachment Moved to S3';
			$status['finished'] = date('Y-m-d H:i:s');
			// Remove temporary Attachment from recorder
			unlink($tmpfile);
        } else {
        	$status['status'] = 'Error';
			$status['description'] = 'There was an error moving the Attachment to S3';
        }

        return $status;
    }

	/**
	* Check S3 and remove the Attachment before deleting the Attachment
	* record
	*/
	public function beforeDelete() {
		// Check S3 and remove attachment if exists
		$s3 = Aws::factory(AWS_CONFIG)->get('S3');
		$s3->registerStreamWrapper();
		$attachment = $this->find('first', array(
			'conditions' => array('Attachment.id' => $this->id),
			'fields' => array('Attachment.id','Attachment.model_type','Attachment.client_id','Attachment.foreign_id','Attachment.dir','Attachment.filename'),
			'contain' => array()
		));
		$model = strtolower($attachment['Attachment']['model_type']);
		$file = 's3://'.VMS_ARCHIVE.'/'.$attachment['Attachment']['client_id'].'/attachments/'.$model.'/'.$attachment['Attachment']['foreign_id'].'/'.$attachment['Attachment']['filename'];
		if(file_exists($file)) {
			unlink($file);
		}
		return true;
	}

	/**
	*
	*
	*/
	public function removeVideoAttachments($video_id = null) {
		if(!$video_id) {
			return;
		}
		$a = $this->find('list',array(
			'conditions' => array('model_type' => 'Video','foreign_id' => $video_id),
			'fields' => array('id'),
			'contain' => array()
		));
		if (!empty($a)) {
			foreach($a as $attachment_id => $attachment) {
				$this->delete($attachment_id);
			}
		}
		return;
	}
}
?>
