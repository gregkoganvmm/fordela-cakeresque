<?php
App::uses('AppModel', 'Model');
/**
 * JobQueue Model
 *
 */
class JobQueue extends AppModel {

/**
 * Use table
 *
 * @var mixed False or table name
 */
	public $useTable = 'job_queue';

/**
 * Display field
 *
 * @var string
 */
	public $displayField = 'type';
        
        
        function updateJob($jobId,$status){
	    //update JobQueue record
	    $this->id = $jobId;
	    $this->save($status);
	}

}
