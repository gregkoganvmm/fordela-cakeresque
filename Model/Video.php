<?php
/**
 * $Id: video.php 13614 2012-03-20 16:43:22Z daniel $ 
 * Short description for video.php
 *
 * Long description for video.php
 *
 * @package       vms
 * @subpackage    vms.app.models
 * @since         v 1.0
 */
/**
 * Video class
 *
 * @uses          AppModel
 * @package       vms
 * @subpackage    vms.app.models
 */
class Video extends AppModel {

	/**
	 * 
	 * @var unknown_type
	 */
	var $name = 'Video';

	/**
	* Description goes here
	*/
	public function mediaInfo($path){
		$result = array(
			'filesize'=>0,
			'runtime'=>null,
			'fps'=>0,
			'width'=>0,
			'height'=>0,
			'bitrate'=>null, //this should be audio and video, but is a placeholder for just video bitrate
			'video_codec'=>null,
			'video_bitrate'=>null,
			'audio_codec'=>null,
			'audio_bitrate'=>null,
			'audio_sample_rate'=>null,
			'format'=>null
		); 
		App::uses('Mediainfo', 'Lib');
		if(is_file($path)){
			$mediaInfo = new Mediainfo($path);
			$result = array_merge($result,$mediaInfo->mediaParams);
			//$this->log($result,'mediainfo_normalized');
			$float_expression ='/[^\d\.]/'; //replace anything that is not a number or period
			
			//set up runtime
			$runtime = array(
			's' => 00,
			'm' => 00,
			'h' => 00
			);
			$duration = explode(' ',$result['runtime']);
			foreach($duration as $time){
				$time_type = str_replace(range(0,9),'',$time);
				$time_value = floatval($time);

				switch($time_type){
					case 'h':
						if(strlen($time_value) == 1){
							$runtime['h'] = '0'.$time_value;
							break;
						}
						$runtime['h'] = $time_value;
					break;	
					
					case 'mn':
						if(strlen($time_value) == 1){
							$runtime['m'] = '0'.$time_value;
							break;
						}
						$runtime['m'] = $time_value;
					break;
					
					case 's':
						if(strlen($time_value) == 1){
							$runtime['s'] = '0'.$time_value;
							break;
						}
						$runtime['s'] = $time_value;
					break;
				}
			}

			$result['bitrate'] = $result['video_bitrate'];
			$result['runtime'] = $runtime['h'].':'.$runtime['m'].':'.$runtime['s'];
			$result['width'] = preg_replace($float_expression, '', $result['width']);
			$result['height'] = preg_replace($float_expression, '', $result['height']);
			$result['filesize'] = filesize($path);
		}
		
		return $result;
	}
}
?>