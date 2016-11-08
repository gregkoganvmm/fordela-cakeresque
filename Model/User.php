<?php
/**
 * Short description for user.php
 *
 * Long description for user.php
 *
 * @package       vms
 * @subpackage    vms.app.models
 * @since         v 1.0
 */
/**
 * User class
 *
 * @uses          AppModel
 * @package       vms
 * @subpackage    vms.app.models
 */
App::uses('Security', 'Utility');

class User extends AppModel
{

	/**
	 * 
	 * @var unknown_type
	 */
	var $actsAs = array(
			    //'Logable',
			    'Containable',
			    //'WhoDidIt',
			    //'Search.Search',
			    //'CsvImport' => array(
				//	'delimiter'  => ','
			    //)
	);
	
	/**
	 * validation functions
	 */
	var $validate = array(
			'username' => array(
				'email' => array(
					'rule' => array('email'),
					'required'=>true,
					'message'=>'Please enter a valid email'
					),
			),
			'password' => array(
				'notempty' => array(
					'rule' => array('notempty'),							
				),
				'required' => array(
				'rule' => array(
					'custom','|[a-zA-Z0-9\_\-]{5,}$|i'),
					'message'=>'Must be 5 characters or longer'
				)
			),
			'name' => array(
				'notempty' => array(
					'rule' => array('notempty'),
					'message'=>'Name can not be empty'
					),
			),
			'active' => array(
				'boolean' => array(
					'rule' => array('boolean'),
					'message'=>'Active can not be empty'
					),
				),
			'timezone' => array(
				'notempty' => array(
					'rule' => array('notempty'),
					'message'=>'Timezone can not be empty'
					),
				),
			'date_format' => array(
				'notempty' => array(
					'rule' => array('notempty'),
					'message'=>'Date Format can not be empty'
					),
				),
			'created_by' => array(
				'numeric' => array(
					'rule' => array('numeric'),
					'message'=>'Created By can not be empty'
					),
				),
			'modified_by' => array(
				'numeric' => array(
					'rule' => array('numeric'),
					'message'=>'Modified By can not be empty'
					),
				),
		);

	/**
	* Check for existing user
	*/
	function validateUniqueUsername(){
		$error=0;
		//Attempt to load based on data in the field
		//$someone = $this->findByUsername($this->data['User']['username']);
		$conditions = array(
			'User.username'=>$this->data['User']['username'],
			//'User.active != 0'
		);
		$recursive = -1;
		$someone = $this->find('first',compact('conditions','recursive'));
		// if we get a result, this user name is in use, try again!
		if (!empty($someone))
		{
			$error++;
		}
		return $error==0;
	}

	
	
  	/**
  	 * Validate Login 
  	 * I don't think this is in use.
  	 * 
  	 * @param array $data User Data
  	 * @return bool
  	 */
	function validateLogin($data)
	{
		$user = $this->find(array('username' => $data['username'], 'password' => md5($data['password'])), array('id', 'username'));
		if(empty($user) == false)
			return $user['User'];
		return false;
	}
	

	/**
	 * HasAndBelongsToMany
	 * @var array 
	 */
	var $hasAndBelongsToMany = array(
		/*
		'Playlist' =>
			array('className' => 'Playlist',
				'joinTable' => 'playlists_users',
				'foreignKey' => 'user_id',
				'associationForeignKey' => 'playlist_id',
				//'conditions' => array('Playlist.active' => 1),
				'order' => '',
				'limit' => '',
				'unique' => 'true',
				'finderQuery' => '',
				'deleteQuery' => '',
				),
		'Video',
		*/
		'Client'=>array(
			'fields'=>'id,name,subdomain,active',
			'with'=>'Membership',
			'order' => 'name'
			)
	);



	
	
	/**
	 * adds a record to the clients_users table
	 * @param int $user_id
	 * @param int $client_id
	 * @param int $user_type_id
 	 * @param int $role
	 * 
	 * @return BOOL
	 */
	function addToClient($user_id, $client_id, $user_type_id = null, $role = VMS_BASIC_USER, $daily_emails = 0){
		
		//Check for UserTypeId first
		if($user_type_id!=NULL)
		{
			$conditions['UserType.id'] = $user_type_id;	
		}
		//Use Role if that was provided
		else
		{
			$conditions['UserType.role'] = $role;
			$conditions['UserType.client_id'] = $client_id;
    		
		}
		$userType =  $this->Membership->UserType->find('first',compact('conditions'));
		
		if(empty($userType))
		{
			return false;
		}
		
		$this->Membership->create();
		$clientuser['Membership']['client_id'] = $client_id;
		$clientuser['Membership']['user_id'] = $user_id;
		$clientuser['Membership']['access'] = $userType['UserType']['access'];
		$clientuser['Membership']['role'] = $userType['UserType']['role'];
		$clientuser['Membership']['user_type_id'] = $userType['UserType']['id'];
		$clientuser['Membership']['daily_emails'] = $daily_emails;
		
		if($this->Membership->save($clientuser)){
			//return true;
			return $this->Membership->id;
		}
		else{
			return false;
		}
	}
	
	function addToPlaylist($user, $playlist_id) {
		// determine if user already has been added to this playlist
		$hasPlaylist = $this->PlaylistsUser->find('count',array('conditions'=>array('PlaylistsUser.user_id'=>$user['User']['id'],'PlaylistsUser.playlist_id'=>$playlist_id)));
		if ($hasPlaylist == 0){
			$user['PlaylistsUser']['playlist_id'] = $playlist_id;
			$user['PlaylistsUser']['user_id'] = $user['User']['id'];
			if($this->PlaylistsUser->save($user)){
				return true;
			}
			else{
				return false;
			}
		} else {
			return;
		}
	}
	
	/**
	 * removes a record to the playlists_users table
	 * @param int $user_id
	 * @param int $playlist_id
	 * 
	 * @return BOOL
	 */
	function removeFromPlaylist($user_id,$playlist_id){
		//$this->User->query("DELETE FROM `playlists_users` WHERE `user_id` = '$id'");
		if($this->PlaylistsUser->delete($user_id,$playlist_id)){
			return true;
		}
		else{
			return false;
		}
	}
	
	
	
	
	/**
	 * removes a record to the clients_users table and all playlists_users records for that client
	 * @param int $user_id
	 * @param int $client_id
	 * 
	 * @return BOOL
	 */	
	function removeFromClient($user_id,$client_id){
		$id = $this->Membership->field('id',array('Membership.client_id'=>$client_id, 'Membership.user_id'=>$user_id) ); 
		
		// Fix for PlaylistMember.membership_id error. Needs the 2nd false arg.
		$this->Membership->unbindModel(
			array('hasAndBelongsToMany' => array('Playlist')),false
		);
		if($this->Membership->delete($id)){
			//Remove playlists_members records for this member/user
			$this->query("
				DELETE GU.*
				FROM playlist_members GU, playlists G
				WHERE G.id = GU.playlist_id
				AND GU.member_id = $id
				AND GU.user_id = $user_id
			");
			
			return true;
		}
		else{
			return false;
		}
	}
	
	/**
	 * checks if there is a record in the clients_users table for a clinet_id user_id pair
	 * @param int $user_id
	 * @param int $client_id
	 * 
	 * @return BOOL
	 */
	function existsInClient($user_id,$client_id){
		$membership = $this->Membership->find('first',array('conditions'=>array('Membership.client_id'=>$client_id, 'Membership.user_id'=>$user_id),'recursive'=>-1 ) ); 
		if(!empty($membership)){
			return $membership['Membership']['id'];
		}
		else{
			return false;
		}
	}
	
	/**
	 * checks if there is a record in the playlists_users table for a playlist_id user_id pair
	 * @param int $user_id
	 * @param int $playlist_id
	 * 
	 * @return BOOL
	 */
	function existsInPlaylist($user_id,$playlist_id){
		if($this->PlaylistsUser->find('first',array('conditions'=>array('PlaylistsUser.playlist_id'=>$playlist_id, 'PlaylistsUser.user_id'=>$user_id),'recursive'=>-1 ) ) ){
			return true;
		}
		else{
			return false;
		}
	}
	
	/**
	 * checks if there is a record in the users_videos table for a video_id user_id pair
	 * @param int $user_id
	 * @param int $video_id
	 * 
	 * @return BOOL
	 */
	function existsInVideo($user_id,$video_id){
		if($this->UsersVideo->find('first',array('conditions'=>array('UsersVideo.video_id'=>$video_id, 'UsersVideo.user_id'=>$user_id),'recursive'=>-1 ) ) ){
			return true;
		}
		else{
			return false;
		}
	}
	
	/**
	 * checks if there is a record in the playlists_users table for a playlist_id user_id pair
	 * @param int $user_id
	 * @param int $model_id the model to check against
	 * @param String $model Model to check (Playlist) 
	 * 
	 * @return BOOL
	 */
	function existsIn($user_id,$model_id,$model){
		//find what the binding table is
		if(strcmp($model,'User') > 0 ){
			$HABTM = 'Users'.$model;
		}
		else{
			$HABTM = $model.'sUser';
		}
		
		if($this->$HABTM->find('first',array('conditions'=>array($HABTM.'.'.strtolower($model).'_id'=>$model_id, $HABTM.'.'.'user_id'=>$user_id),'recursive'=>-1 ) ) ){
			return true;
		}
		else{
			return false;
		}
	}
	
	function _create($create_data,$client_id){
		
		$this->create();
	 			
 		// default values
		$create_data['User']['client_id'] = $client_id;
		$create_data['User']['active'] = "1";
		$create_data['User']['permissions'] = "10";
		$create_data['User']['acl'] = $this->UserType->field('acl',array('id'=>10));
		
		return $create_data;
	}
	
	function _update($update_data, $existing_user){
		// there is 2 options here, 
				 
		// option 1:
		// load the current row, and merge it with the new data
	 	$this->recursive = -1;
	 	$update_data['User'] = array_merge($existing_user['User'],$update_data['User']);
	 	//debug($update_data);
		// option 2:
	 	// set the model id
	 	//$this->id = $id;
	 	return $update_data;
	}
	
	
	
	function import($data, $client_id) {	
		// create a message container
		$exists = false;
		$error = false;
		$existingUser = false;
		
		$return = array(
			'messages' => array(),
			'errors' => array()
		);
		
		$messages = array(
			'new_pass'=>array(),
			'new'=>array(),
			'error'=>array(),
			'errors'=>array()
		);

		if(!isset($data['User'][0]['username'])){
			$return['import']['errors']['import'] = 'Error: necessary fields not mapped -- email';
			return $return;
		}
		
 		foreach ($data['User'] as $user) 
 		{
			if (empty($user['username']) ) {
                Continue; // skip because this isn't an email
            }

            $exists = false;
			$error = false;
			$existingUser = false;

            $tags = (isset($user['tags'])) ? $user['tags'] : null;
		
 			$User = $this->findByUsername($user['username']);
 			if($User){
 				$membership = $this->Membership->find('first',array('conditions'=>array('Membership.user_id'=>$User['User']['id'],'Membership.client_id'=>$client_id), 'recursive'=>-1));
                if (!$membership) {
                    $membership_id = $this->addToClient($User['User']['id'],$client_id);
                    $membership = $this->Membership->find('first',array('conditions'=>array('Membership.user_id'=>$User['User']['id'],'Membership.client_id'=>$client_id), 'recursive'=>-1));
                }
 			}
			$username = $user['username'];
			
			// we have an id, so we update
 			if ($User && $client_id == $membership['Membership']['client_id'] ) 
 			{
 				//$this->data=$this->_update($this->data, $User);
				$exists = $existingUser = true;
 				$message = $username;
				$messages['existing'][] = $message;
			}
			// or create a new record
			else if(!$User)
			{
				if(isset($user['password']) && !empty($user['password'])){
					$pass = $user['password'];
					$user['password'] = Security::hash($user['password'], NULL, true);
				}
				else{
					$pass = $this->_generate_password($letters=6);
					$user['password'] = Security::hash($pass, NULL, true);
				}
				$user['timezone'] = 'US/Pacific';
				
				if(isset($user['first_name']) && isset($user['last_name'])){
					$user['name'] = $user['first_name'].' '.$user['last_name'];
				}
				
	 			$user = $this->create($user);
				

				
	 			$message = $username.':'.$pass;
	 			$messages['new_pass'][] = $message;
			}
			//existing user new client
			else
			{
				$user_id=$User['User']['id'];
				$message = $username;
				$messages['new'][] = $message;
				$existingUser = true;
				//$this->addToClient($user_id,$client_id);
				//return $return;		
			}
            $this->log($user,'imported_customers');
			$this->set($user);
			// validate the row
			if (!$this->validates()) 
			{
				//$this->_flash(null,'warning');
				$return['messages'][] = $username.' --- Invalid Data';
				$return['messages']['error'][] = $username.' --- Invalid Data';
			}
 			// save the row
 			if (!$error && !$existingUser && !$this->save($user)) 
 			{
				$return['messages'][] = $username.' --- Failed to Save.';
				$return['messages']['error'][] = $username.' --- Failed to Save.';
			}
 			// success message!
			else if (!$error && !$exists) 
			{
				
				if(!$existingUser){
					$user_id = $this->id;
				}
				$membership_id = $this->addToClient($user_id,$client_id);

                // Update membership record if tags
                if( !empty($membership_id) && !empty($tags) ) {
                    $this->Membership->id = $membership_id;
                    $this->Membership->savefield('tags',$tags);
                }

				$return['messages'][] = $message;
			}
			else{
				
				$return['errors'][] = $message;	
			}

 		}
 		$return['import'] = $messages;
		
 		return $return;
 		
	}

	
	/**
	 * 
	 * Enter description here ...
	 * @param unknown_type $user_id
	 * @param unknown_type $client_id
	 */
	function getRequiredVideos($user_id,$client_id){
		$conditions['Video.active'] = 1;
		$conditions['Video.deleted'] = 0;
		$conditions['Video.client_id'] = $client_id;
		$conditions['PlaylistItem.required'] = 1;
		$this->Video->PlaylistItem->Behaviors->attach('Containable');
		$videos = $this->Video->PlaylistItem->find('all', array(
				'conditions'=>$conditions,
				'order'=>'PlaylistItem.weight ASC',
				'contain'=>array(
					'Video'
				)
			)
		);
		//debug($videos);
		return $videos;
	}
	
	/* SEARCH PLUGIN CODE */
	
	public $filterArgs = array(
	array(
	    	'name' => 'username', 
	    	 'type' => 'query',
	    	 'method' => 'nameSearch'
	),
	array(
	    	'name' => 'id_search', 
	    	 'type' => 'query',
	    	 'method' => 'idSearch'
	)
	);
	
	
	public function nameSearch($data = array()) {
		$filter = $data['username'];
		$cond = array(
	            'OR' => array(
					'User.name LIKE' => '%' . $filter . '%',
	                'User.first_name LIKE' => '%' . $filter . '%',
                	'User.last_name LIKE' => '%' . $filter . '%',
        			'User.username LIKE' => '%' . $filter . '%',
		));
		return $cond;
	}
	
	public function idSearch($data = array()) {
		$filter = $data['id_search'];
		$cond = array(
	            'OR' => array(
	                'User.id' => $filter
		));
		return $cond;
	}
	/* END SEARCH PLUGIN CODE */
	
	
	/**
   	 * Generates Random Passwords for new users.
   	 * 
   	 * @param int $letters Number of letters in password
   	 * @return string Random Password
   	 */
   	function _generate_password($length = 8, $possible = '0123456789abcdefghijklmnopqrstuvwxyz'){
   		
   		// initialize variables
		$password = "";
		$i = 0;
 
		// add random characters to $password until $length is reached
		while ($i < $length) {
			// pick a random character from the possible ones
			$char = substr($possible, mt_rand(0, strlen($possible)-1), 1);
 
			// we don't want this character if it's already in the password
			if (!strstr($password, $char)) { 
				$password .= $char;
				$i++;
			}
		}
		return $password;
	}
	
}
?>