<?php
class Message extends AppModel {

	/**
	 * 
	 * @var string $name Message
	 */
	var $name = 'Message';
	
	/**
	 * Saves Outgoing email Messages to the Database
	 * 
	 * @param string $to Email Address
	 * @param string $subject Subject
	 * @param string $body Message Body
	 * @param string $template
	 * @param array $template_vars Key=>Value Variables for email template
	 */
	function creatde($to,$subject,$body,$template=null,$template_vars=array()){
		$body = (is_array($body))? serialize($body) : $body;
		$template_vars = (!empty($template_vars) && is_array($template_vars))? serialize($template_vars) : NULL;
		parent::create();
		//$this->data['Message']['to'] = $to;
		//$this->data['Message']['subject'] = $subject;
		//$this->data['Message']['body'] = $body;
		//$this->data['Message']['template'] = $template;
		$this->data['Message'] = compact('to','subject','body','template','template_vars');
		$this->save($this->data);
	}

}
?>