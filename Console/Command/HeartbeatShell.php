<?php
App::import('Core', 'Controller');
App::import('Controller', 'Notifications.Send');

App::import('Vendor', 'Resque.Resque', array('file' => 'php-resque' . DS . 'lib' . DS . 'Resque.php'));

class HeartbeatShell extends AppShell {
   public $uses = array('JobqueueWorker','JobQueue');

   public $retry = 3;

   //public function perform()
   //{
   //    $this->initialize();
   //    $this->{array_shift($this->args)}();
   //}
   /**
    * Check if job needs to be restarted
    *
    *
    **/

   public function checkJobs(){
	Resque::setBackend(Configure::read('Resque.Redis.host') . ':' . Configure::read('Resque.Redis.port'));
	//check jobqueue for any jobs that are processing
	//if job has been processing for more then an hour restart job and worker that was supposed to be doing the job
	$conditions['Not']['status'] = array('Finished'); //Jobs Stuck Processing, Reset or Error
	$conditions['failed'] = 0;

	$jobs = $this->JobQueue->find('all',array('conditions'=>$conditions));

	$flag_worker_restart = false;

	foreach($jobs as $job){
	    //retry a set amount of times
	    if(($job['JobQueue']['retry'] <= $this->retry)){

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
	       ){
		  //reset Job
		  $this->out('Reset: '.$job['JobQueue']['id']);

		  $this->reset($job['JobQueue']['id'],true);

		  //add to retry counter
		  $this->JobQueue->id = $job['JobQueue']['id'];
		  $this->JobQueue->saveField('retry',($job['JobQueue']['retry']+1));
	       }
	       else{
		  $this->out('Job did not meet conditions to reset');
		  $this->out($job['JobQueue']['status'].'== Error');
		  $this->out(strtotime(time()). '>' .$created_30);
		  $this->out(strtotime(time()).'>'. $fetched_60);
	       }
	    }
	    //Send an email saying job failed and how many retry attempts
	    else{
	       $this->out('Failed: '.$job['JobQueue']['id']);
	       $this->JobQueue->id = $job['JobQueue']['id'];
	       $this->JobQueue->set(array(
	       		'failed' => 1,
	       		'status' => 'Failed'
	       ));
	       $this->JobQueue->save();

	       $flag_worker_restart = true;

	       $this->Send = new SendController();
	       $this->Send->job_error($job);
	    }
	 }
	 if($flag_worker_restart == true){
	 	$this->out('RESTART WORKERS');
	 }
    }

    public function reset($id = null) {
		$this->JobQueue->id = $id;
		if (!$this->JobQueue->exists()) {
			throw new NotFoundException(__('Invalid job'));
		}

		$job = $this->JobQueue->read(null,$id);

		$queue = $job['JobQueue']['queue'];
		$shell = str_replace('Shell','',$job['JobQueue']['type']);
		$function = $job['JobQueue']['function'];

		$params = unserialize($job['JobQueue']['params']);
		unset($params[0]);

		$this->_queue($queue,$shell,$function,$params,$id);
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
  function _queue($queue,$shell,$function,$params=array(),$jobId = null){

	  //ad the shell function to run to the begining of the params array
	  array_unshift($params,$function);

	  //save job to DB table
	  $job = array(
		  'queue'=>$queue,
		  'type'=>$shell.'Shell',
		  'function'=>$function,
		  'params'=>serialize($params),
	  );

	  //New Job
	  if(empty($jobId)){
	      $this->JobQueue->create($job);
	      $this->JobQueue->save();

	      $jobId = $this->JobQueue->id;
	  }
	  else{
	      //Reset Job
	      $this->JobQueue->id;
	      $job['JobQueue']['status'] = 'Reset';
	      $job['JobQueue']['description'] = '';
	      $job['JobQueue']['finished'] = '';
	      $this->JobQueue->save($job['JobQueue']);
	  }

	  array_push($params,$jobId);

	  //send job to Resque server
	  Resque::enqueue(
			  $queue,
			  $shell.'Shell',
			  $params
	  );
  }
}
?>
