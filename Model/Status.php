<?php
/**
 * Short description for video_status.php
 *
 * Long description for video_status.php
 *
 * @package       vms
 * @subpackage    vms.app.models
 * @since         v 1.0
 */
/**
 * Status class
 *
 * @uses          AppModel
 * @package       vms
 * @subpackage    vms.app.models
 */
class Status extends AppModel {

  var $name = 'Status';
  var $useTable = 'status';

  var $hasMany = array(
		'Video'=>array(
			'className'  => 'Video',
			'dependent'  =>  true,
			'foreignKey' => 'status_id'
		)
	);
	
	/**
	 * 
	 * returns client specific statuses if there are any otherwise just uses the defualts
	 * @param String $model
	 * @param Int $client_id
	 * @return Array client specific statuses if there are any otherwise just uses the defualts 
	 */
	function getStatuses($model,$client_id=null){
		$order = array('order'=>'asc');
		if(empty($client_id)){
			$client_id = $_SESSION['Auth']['User']['client_id'];
		}
		//TODO Add caching
		$statuses = $this->find('list',array('conditions'=>array('Status.model'=>$model,'Status.client_id'=>$client_id),'order'=>$order));
		if(empty($statuses)){
			$statuses = $this->find('list',array('conditions'=>array('Status.model'=>$model,'Status.client_id'=>0),'order'=>$order));
		}
		return $statuses;
	}
}
?>
