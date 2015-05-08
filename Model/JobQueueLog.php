<?php
App::uses('AppModel', 'Model');
/**
 * JobQueueLog Model
 *
 */
class JobQueueLog extends AppModel {

	var $name = 'JobQueueLog';
    var $primaryKey = '_id';
    var $useDbConfig = 'mongo';
	var $useTable = 'jobqueue';

	public function schema($field = false) {
		// TODO: Update additional fields
		$this->_schema = array(
	            '_id' => array('type' => 'integer', 'primary' => true, 'length' => 40),
				'message' => array('type' => 'string'),
				//'some_id' => array('type' => 'integer'),
	        	'datetime'=>array('type'=>'datetime'),
		);
		return $this->_schema;
	}

}
