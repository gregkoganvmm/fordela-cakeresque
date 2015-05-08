<?php
class PlayerSession extends AnalyticsAppModel {
	var $name = 'PlayerSession';
	var $primaryKey = '_id';
	var $useDbConfig = 'mongo';
	//var $useTable = 'statistics';
	var $useTable = 'sess';
	
	
	
	function schema() {
		$this->_schema = array(
	            '_id' => array('type' => 'integer', 'primary' => true, 'length' => 40),
				'ip_address' => array('type' => 'string'),
				'client_id' => array('type' => 'integer'),
				'plays' => array('type' => 'integer'),
				'embed_url' => array('type' => 'string'),
				'country' => array('type' => 'string'),
				'location' => array('type' => 'string'),
				'lat' => array('type' => 'float'),
				'lon' => array('type' => 'float'),
	        	'created'=>array('type'=>'datetime'),
		);
		return $this->_schema;
	}
}