<?php
class MembershipLogin extends AppModel {
	
	var $name = 'MembershipLogin';
	
	var $actsAs = array('Containable');
	
	var $belongsTo = array(
		'Membership'=>array(
			'counterCache' => true
		)
	);	
	
  	function record_login($user){
		
		$this->data['name'] = $user['User']['name'];
		$this->data['user_id'] = $user['User']['id'];
		$this->data['membership_id'] = $user['Client']['Membership']['id'];
		$this->data['client_id'] = $user['Client']['id'];
		$this->data['ip'] = env('REMOTE_ADDR');
		$this->data['host'] = env('HTTP_HOST');
		$this->data['created'] = NOW;

		$this->save($this->data);
		
		return;
  	}
}
?>