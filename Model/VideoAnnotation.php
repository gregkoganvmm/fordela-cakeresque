<?php
/**
 * Short description for video_annotation.php
 *
 * Long description for video_annotation.php
 *
 * @package       vms
 * @subpackage    vms.app.models
 * @since         v 1.0
 */
/**
 * VideoAnnotation class
 *
 * @uses          AppModel
 * @package       vms
 * @subpackage    vms.app.models
 */
class VideoAnnotation extends AppModel {
	
	/**
	 * 
	 * @var array
	 */
	var $belongsTo = array(
		'Video' => array(
			'foreignKey' => 'video_id'
		),
		'User',
		'Playlist'
	);

}
?>
