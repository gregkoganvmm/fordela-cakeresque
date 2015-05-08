<?php
class UserType extends AppModel {

	var $name = 'UserType';
	var $actsAs = array('Containable', 'Acl' => array('type'=> 'requester'));
	
	var $validate = array(
		'name' => array(
			'notempty' => array(
				'rule' => array('notempty'),
				'message' => 'Please enter a name'							
			)
		)
	);

//	function parentNode(){
//        if (!$this->id) {
//            return null;
//        }
//        $data = $this->read();
//        if (!$data['UserType']['client_id']){
//            return null;
//        } else {
//            return array('model' => 'Client', 'foreign_key' => $data['UserType']['client_id']);
//        }
//    }

	public function parentNode() {
		if (!$this->id && empty($this->data)) {
		    return null;
		}
		if (isset($this->data['UserType']['client_id'])) {
		    $clientId = $this->data['UserType']['client_id'];
		} else {
		    $clientId = $this->field('client_id');
		}
		if (!$clientId) {
		    return null;
		} else {
		    return array('Client' => array('id' => $clientId));
		}
	    }
	
	/**
	 * create default UserType records for a new client
	 * @param Int $client_id Client Id
	 * @return null
	 */	
	function defaultRecords($client_id){

		$sql = "Insert into user_types (client_id,name,description,role,modified,created)
				SELECT $client_id as `client_id`, `name`, `description`, `role`,'".NOW."' as `modified`,'".NOW."' as `created` 
				FROM `user_types` 
				WHERE client_id = 0";
		
		$this->query($sql);
	}
	
	
	/**
	 * 
	 * Enter description here ...
	 */
	function initPermissions($user_type_id, $preset, $client_id)
	{
		$userTypes = $this->getPresets($client_id);
		$this->id = $user_type_id;
		$collection = new ComponentCollection();
		$this->Acl = new AclComponent($collection);
		
		if(!empty($userTypes[$preset]['allow']) and is_array($userTypes[$preset]['allow'])){
			foreach($userTypes[$preset]['allow'] as $allowedPerm)
			{
				if($this->Acl->allow($this, $allowedPerm))
				{
					$this->log('ALLOW: '.$preset.': '.$allowedPerm,'UserTypePresets');
				}
				else
				{
					$this->log('ERROR: ALLOW: '.$preset.': '.$allowedPerm,'UserTypePresets');
				}
			}
		}
		if(!empty($userTypes[$preset]['deny']) and is_array($userTypes[$preset]['deny'])){
			foreach($userTypes[$preset]['deny'] as $deniedPerm)
			{
				if($this->Acl->deny($this, $deniedPerm))
				{
					$this->log('DENY: '.$preset.': '.$deniedPerm,'UserTypePresets');
				}
				else
				{
					$this->log('ERROR: DENY: '.$preset.': '.$deniedPerm,'UserTypePresets');
				}
			}
		}
	}
	
	/**
	 * 
	 * Enter description here ...
	 * @param int $client_id
	 * @return array|boolean
	 */
	function getPresets($client_id = null){
		
		if(!empty($client_id) and file_exists(APP . 'Config' . DS.DS.'acl'.DS.'clients'.DS.$client_id.'.ini'))
		{
			$aclFilename = APP . 'Config' . DS.DS.'acl'.DS.'clients'.DS.$client_id.'.ini';
		}
		else
		{
			$aclFilename = APP . 'Config' . DS.DS.'acl'.DS.'user_type_presets.ini';
		}
		
		$userTypes = parse_ini_file (  $aclFilename ,  $process_sections = true , $scanner_mode = INI_SCANNER_NORMAL  );
		
		if(!empty($userTypes))
		{
			return $userTypes;
		}
		else
		{
			return false;
		}
	}
}
?>