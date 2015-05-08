<?php
class SendController extends NotificationsAppController {

	var $uses = array('Client','Video','User','Playlist','Membership','Domain');

	/**
	 * components
	 *
	 * @var unknown_type
	 */
	var $components = array('Email');

	var $autoRender = false;

	var $templateVars = array(
		'clientid'=>null,
		'clientname'=>null,
		'clientlogo'=>null,
		'white_label'=>null
	);

	var $viewVars = array(
	);

	function beforeFilter(){
		$this->Auth->allow();
		$this->log($this->here,'notifications_send');
	}

	/**
	 *
	 * sets the Client name logo and id for the email template
	 *
	 * @param int client_id
	 */
	function _set_client($client_id){
		$vmsUrl =  'https';
		if(!empty($client_id)){
			$client = $this->Client->find('first',array('conditions'=>array('Client.id'=>$client_id),'fields'=>array('Client.name','Client.custom_logo', 'Client.subdomain','Client.date_format', 'Client.email','Client.white_label') ));
			$domain = $this->Domain->find('first',array('conditions'=>array('Domain.client_id'=>$client_id)));
			$this->templateVars['clientid'] = $client_id;
			$this->templateVars['clientname'] = $client['Client']['name'];
			$this->templateVars['clientemail'] = $client['Client']['email'];
			$this->templateVars['clientlogo'] = $client['Client']['custom_logo'];
			$this->templateVars['subdomain'] = $client['Client']['subdomain'];
			$this->templateVars['date_format'] = $client['Client']['date_format'];
			$this->templateVars['white_label'] = $client['Client']['white_label'];

			//$vmsUrl .=  '://'.$client['Client']['subdomain'].'.fordela.com'; // SUBDOMAIN_URL_PREFIX
			//
			//$this->templateVars['vmsUrl'] = $vmsUrl;
			if( !empty($client['Client']['subdomain']) ){
				$vmsUrl .= '://'.$domain['Domain']['subdomain'].'.'.$domain['Domain']['sld'].'.'.$domain['Domain']['tld'];
			    }else{
				$vmsUrl .=  '://vms.'. SUBDOMAIN_URL_PREFIX;
			    }

			    $this->templateVars['vmsUrl'] = $vmsUrl;
		}
		else{
			$this->templateVars['clientid'] = null;
			$this->templateVars['clientname'] = 'Fordela';
			$this->templateVars['clientlogo'] = null;
			$this->templateVars['subdomain'] = null;
			$this->templateVars['date_format'] = 'm/d/y';
			$vmsUrl .=  '://vms.'. '.fordela.com'; // SUBDOMAIN_URL_PREFIX

			$this->templateVars['vmsUrl'] = $vmsUrl;
		}
		$this->set('templateVars',$this->templateVars);
	}

	/**
	 * Called from RandomTaskShell function login_notification
	 *
	 *
	 */
	function notify_login($email, $user_id, $client_id)
	{
		$this->User->contain();
		$user = $this->User->findById($user_id,'id, username, name');
		$managerUserId = $this->User->field('id',array('username' => $email));
		$this->_set_client($client_id);
		$this->templateVars['username'] = $user['User']['username'];
		$this->templateVars['managerUserId'] = $managerUserId;
		$viewVars['templateVars'] = $this->templateVars;
		$viewVars['date'] = date("F j, Y" ,mktime(0, 0, 0, date("m")  , date("d")-1, date("y")));
		$subject = "[Fordela] {$user['User']['username']} logged in to {$this->templateVars['subdomain']}.fordela.com";
		$this->_email($subject,null,$to=$email,$from=null,$template='notify_login',$viewVars,$cc=null,$bcc=null);
		return $user;
	}


	/**
	 *
	 * Called From : Analytics:DailyDigest
	 * Sends an email with the daily analytics information
	 * @param Array $recent_logins
	 * @param int $clinet_id
	 */
	function daily_digest($recent_logins,$client_id)
	{
		$date = date("F j, Y" ,mktime(0, 0, 0, date("m")  , date("d")-1, date("y")));
		$subject= '[Fordela] '.$date.' Analytics Report';

		$viewVars = array();
		$viewVars['users'] = $recent_logins; // array of users who logged in for the day
		$viewVars['date'] = $date;

		$this->_set_client($client_id);
		$viewVars['templateVars'] = $this->templateVars;

		$conditions['Membership.daily_emails'] = 1;
		$conditions['Membership.client_id'] = $client_id;

		$contain = array('User'=>array('fields'=>array('username','name')));

		$admins = $this->Membership->find('all',array('conditions'=>$conditions, 'contain'=>$contain, 'fields'=>array('Membership.id')));

		$bcc = array();

		foreach($admins as $admin){
			//$to[$admin['User']['username']] = $admin['User']['name'];
			$bcc[$admin['User']['username']] = $admin['User']['name'];
		}

		$this->_email($subject,null,$to='clientservices@fordela.com',$from=null,$template='daily_digest',$viewVars,$cc=null,$bcc);

		return $bcc;
	}

	function job_error($job){
		$subject = '[Fordela] Job Queue Fail';
		$to = array('dev@fordela.com' => 'Dev' );

		$viewVars = array();
		$viewVars['job'] = $job;

		$this->_set_client(1);
		$viewVars['templateVars'] = $this->templateVars;

		$this->_email($subject,null,$to,$from=null,$template='job_error',$viewVars,$cc=null,$bcc=null);
	}

	function storage_report() {

		$clients = $this->Client->find('all',array(
			'fields' => array(
				'Client.id',
				'Client.name',
				'Client.email',
				'Client.subdomain',
				'Client.video_storage',
				'Client.versions_storage',
				'Client.audio_storage',
				'Client.total_storage'
			),
			'order' => array('Client.id' => 'asc')
		));

		$subject = '[Fordela] Storage Report - based on records';
		$to = array('dev@fordela.com' => 'DEV');

		$viewVars = array();
		$viewVars['clients'] = $clients;

		$this->_email($subject,null,$to,$from=null,$template='storage_report',$viewVars,$cc=null,$bcc=null);
	}
}
?>
