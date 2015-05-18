<?php
App::import('Core', 'Controller');
App::import('Controller', 'Notifications.Send');

class HeartbeatShell extends AppShell {
	
	public $uses = array('JobQueue');
	public $retry = 3;

	// Don't think this function is needed in this shell
	/*public function perform()
	{
		$this->initialize();
		$this->{array_shift($this->args)}();
	}*/

   /**
    * Check if job needs to be restarted
    *
    *
    **/
	public function checkJobs(){
		$conditions['Not']['status'] = array('Finished'); 
		$conditions['failed'] = 0;
		//get all jobs stuck "Processing", "Reset" or "Error"
		$jobs = $this->JobQueue->find('all',array('conditions'=>$conditions));

		foreach($jobs as $job){
			//retry a set amount of times
			if(($job['JobQueue']['retry'] <= $this->retry)) {

				$created_30 = strtotime("+30 minutes",strtotime($job['JobQueue']['created']));
				$fetched_60 = strtotime("+90 minutes",strtotime($job['JobQueue']['fetched']));

				$this->out($job['JobQueue']['id'].':retry-'.($job['JobQueue']['retry']+1));
				$this->out('30 min from created: '.date("Y-m-d H:i:s",$created_30));
				$this->out('60 min from featched: '.date("Y-m-d H:i:s",$fetched_60));
				$this->out(time());

				if(
					($job['JobQueue']['status'] == 'Error') ||
					(empty($job['JobQueue']['fetched'])  && time() > $created_30) || // Job was not fetched and has been in queue over 30 min
					(time() > $fetched_60) //if fetched is over an hour ago
				) {
					//reset Job
					$this->out('Reset: '.$job['JobQueue']['id']);

					$this->reset($job['JobQueue']['id'],true);

					//add to retry counter
					$this->JobQueue->id = $job['JobQueue']['id'];
					$this->JobQueue->saveField('retry',($job['JobQueue']['retry']+1));
				} else {
					$this->out('Job did not meet conditions to reset');
					$this->out($job['JobQueue']['status'].'== Error');
					$this->out(strtotime(time()). '>' .$created_30);
					$this->out(strtotime(time()).'>'. $fetched_60);
				}
			} else {
				//Send an email saying job failed and how many retry attempts
				$this->out('Failed: '.$job['JobQueue']['id']);
				$this->JobQueue->id = $job['JobQueue']['id'];
				$this->JobQueue->set(array(
					'failed' => 1,
					'status' => 'Failed'
				));
				$this->JobQueue->save();

				$this->Send = new SendController();
				$this->Send->job_error($job);
			}
		}
    }

    public function reset($id = null) {
		$this->JobQueue->id = $id;
		if (!$this->JobQueue->exists()) {
			throw new NotFoundException(__('Invalid job'));
		}
		$job = $this->JobQueue->read(null,$id);
		$shell = str_replace('Shell','',$job['JobQueue']['type']);
		$params = unserialize($job['JobQueue']['params']);
		unset($params[0]);
		$this->_queue($job['JobQueue']['queue'], $shell, $job['JobQueue']['function'], $params, $id);
   }

	/**
	* Makes a Job For the Resque Server
	* Saves a copy to the JobQueue table for UI
	*
	* @param String $queue the name of the queue to add the job
	* @param String $shell the name of the shell script that will process the job
	* @param String $function the name of the function in the shell script to call
	* @param Array $params any params the function needs
	*
	**/
	function _queue($queue,$shell,$function,$params=array(),$jobId = null)
	{
		//add shell function to beginning of the params array
		array_unshift($params,$function);
		$job = array(
			'queue'=>$queue,
			'type'=>$shell.'Shell',
			'function'=>$function,
			'params'=>serialize($params),
		);
		if(empty($jobId)){ //New Job
			$this->JobQueue->create($job);
			$this->JobQueue->save();
			$jobId = $this->JobQueue->id;
		} else { //Reset Job
			$this->JobQueue->id;
			$job['JobQueue']['status'] = 'Reset';
			$job['JobQueue']['description'] = '';
			$job['JobQueue']['finished'] = '';
			$this->JobQueue->save($job['JobQueue']);
		}
		//add jobId to end of params array
		array_push($params,$jobId);
		$redisJobId = CakeResque::enqueue($queue,$shell.'Shell',$params);
	}
}
?>
