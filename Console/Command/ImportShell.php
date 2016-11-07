<?php 
App::import('Core', 'Controller');
// Will probably need to send Email too
App::uses('CakeEmail', 'Network/Email');
// App::import('Controller', 'Notifications.Send');

class ImportShell extends Shell {

	var $status = array();

	public $uses = array('JobQueue','Client','User');

	public function perform(){
		$this->initialize();
		$this->{array_shift($this->args)}();
	} 

	public function importUsers(){
		
		$manager = $this->args[0];
		$users = $this->args[1];

		$messages = $this->User->import($users,$manager['client_id']);

		$this->log($messages,'imported_customers');
		$this->log('Import complete - Sending notification','imported_customers');

        $arrValues = array_values($this->args);
		$jobId = end($arrValues);
		$this->status = array('status'=>'Finished','description'=>'Importing users complete');
		$this->JobQueue->updateJob($jobId,$this->status); 

		$this->send_notification($manager);
	}

	public function send_notification($manager = array()){
		if(empty($manager['user_id'])){
			$this->_end();
		}
		if($manager['user_id'] == 2285){
			$manager['username'] = 'dev@fordela.com';
		}

		//TODO: Maybe use Notifications.Send instead of CakeEmail and 
		// create a function / template for this email
		// Or see - http://book.cakephp.org/2.0/en/core-utility-libraries/email.html
		// for this format
		$Email = new CakeEmail();
		$Email->from(array('support@fordela.com' => 'Fordela.com'))
			->domain('www.fordela.com')
			//->template('user_import','default')
			//->emailFormat('html')
			->to($manager['username'])
			->subject('Customer Import Complete')
			//->viewVars(array('value'=>'12345'))
			->send();

		//$this->log('Notification sent','import_customers');
	}
}
?>