<?php
/**
 * Short description for video_pass.php
 *
 * This Class is For the Association between Videos and Users
 *
 * @package       vms
 * @subpackage    vms.app.models
 * @since         v 1.0
 */
/**
 * VideoPass class
 *
 * @uses          AppModel
 * @package       vms
 * @subpackage    vms.app.models
 */
class VideoPass extends AppModel {
	
	/**
	 * Name
	 * @var unknown_type
	 */
	var $name = 'VideoPass';
	
	/**
	 * Db Table
	 * @var unknown_type
	 */
	var $useTable = 'users_videos';

	/**
	 * BelongsTo
	 * @var array
	 */
	var $belongsTo = array(
		'User','Video'
	);
}
?>