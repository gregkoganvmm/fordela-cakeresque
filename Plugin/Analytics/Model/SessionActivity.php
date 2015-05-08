<?php
class SessionActivity extends AnalyticsAppModel {
	var $name = 'SessionActivity';
	var $primaryKey = '_id';
	var $useDbConfig = 'mongo';
	//var $useTable = 'statistics';
	
	
	//var $belongsTo = array('PlayerSession');
	
	function schema($field = false) {
		$this->_schema = array(
	            '_id' => array('type' => 'integer', 'primary' => true, 'length' => 40),
				'session_id' => array('type' => 'string'),
				'client_id' => array('type' => 'integer'),
				'playlist_id' => array('type' => 'integer'),
				'video_id' => array('type' => 'integer'),
				'user_id' => array('type' => 'integer'),
				'membership_id' => array('type' => 'integer'),
				'user_type_id' => array('type' => 'integer'),
				'video_length' => array('type'=>'float'),
				'playthrough' => array('type' => 'integer'),
				'ptp'=>array(),
				'activity'=>array(
					'name' => array('type'=>'string'),
					'time' => array('type'=>'float'),
					'value' => array('type'=>'float'),
					'created' => array('type'=>'datetime'), //for legacy data evalution
				),
				'url' => array('type' => 'string'),
				'created'=>array('type'=>'datetime'),
				'modified'=>array('type'=>'datetime'),
		
		);
		return $this->_schema;
	}
	
	function calculatePlaythrough()
	{
		$m = $this->getMongoDb();
		
	}
}