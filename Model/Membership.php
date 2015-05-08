<?php
/**
 * Short description for membership.php
 *
 * Long description for membership.php
 *
 *$Id: membership.php 1884 2010-05-06 17:24:16Z daniel $
 *
 * @package       vms
 * @subpackage    vms.app.models
 * @since         v 3.1.x
 */
/**
 * Membership class
 *
 * @uses          AppModel
 * @package       vms
 * @subpackage    vms.app.models
 */
class Membership extends AppModel {
	var $useTable = 'clients_users';
	var $actsAs = array(
		'Containable', 
		'Acl' => array('type'=> 'requester'),
	);

	/**
  	 * BelongsTo
	 * @var array 
	 */
	var $belongsTo = array(
		'Client'=>array(
			'className' => 'Client',
			'fields'=>array(
				'id', 
				'name',
				'email',
				'subdomain',
			)
		),
		'Client2'=>array( // Added for additional counterCache
			'className' => 'Client',
			'foreignKey' => 'client_id',
			'fields'=>array(
				'id', 
				'name',
				'email',
				'subdomain',
			)
		),
		'User',
		'UserType' =>
			array('className'  => 'UserType',
				'conditions' => '',
				'order'      => '',
				//'foreignKey' => 'permissions',
				'dependent'  => false
			),
	);
	
	/**
	* HasAndBelongsToMany
	* @var array
	*/
	var $hasAndBelongsToMany = array(
	 	'Playlist' => array(
				'className' => 'Playlist',
				'joinTable' => 'playlist_members',
				'foreignKey' => 'member_id',
				'associationForeignKey' => 'playlist_id',
				//'conditions' => array('Playlist.active' => 1),
				'order' => '',
				'limit' => '',
				'unique' => 'true',
				'finderQuery' => '',
				'deleteQuery' => '',
		),
		'Video'=> array(
			'className' => 'Video',
			'joinTable' => 'video_members',
			'foreignKey' => 'member_id',
			'associationForeignKey' => 'video_id',
			//'conditions' => array('Playlist.active' => 1),
			'order' => '',
			'limit' => '',
			'unique' => 'true',
			'finderQuery' => '',
			'deleteQuery' => '',
		),
	);

	
	/**
	* HasAndBelongsToMany
	* @var array
	*/
	
	var $hasMany = array(
		'PlaylistMember' => array(
			'className' => 'PlaylistMember',
			'foreignKey' => 'member_id',
		),
		'VideoMember'=> array(
			'className' => 'VideoMember',
			'foreignKey' => 'member_id',
		),
	);
	
	
	
	public function parentNode() {
		if (!$this->id && empty($this->data)) {
		    return null;
		}
		if (isset($this->data['Membership']['user_type_id'])) {
		    $userTypeId = $this->data['Membership']['user_type_id'];
		} else {
		    $userTypeId = $this->field('user_type_id');
		}
		if (!$userTypeId) {
		    return null;
		} else {
		    return array('UserType' => array('id' => $userTypeId));
		}
	    }
	
//	function parentNode(){
//		if (!$this->id) {
//			return null;
//        }
//		$data = $this->read();
//		if (!$data['Membership']['user_type_id']){
//			return null;
//		} else {
//			return array('model' => 'UserType', 'foreign_key' => $data['Membership']['user_type_id']);
//		}
//	}
	
	/**
	 * 
	 * Updates field values for video_count, playlist_count, and access_count
	 * @param int $membership_id
	 */
	function access_count($membership_id){
		// defaults
		$video_count = 0;
		$playlist_count = 0;
		$access_count = 0;
		$access_array = array();
		
		$this->contain('Playlist','Video');
		$membership = $this->find('first', array('conditions' => array('Membership.id' => $membership_id)));
		$this->id = $membership_id;
		
		// Videos
		if(!empty($membership['Video'])){
			//$video_membership_array = Set::extract('/Video/VideoMember/id',$membership);
			$video_count = count($membership['Video']);
		}
		
		// Playlists
		if(!empty($membership['Playlist'])){
			$access_array = Set::extract('/Playlist/video_count',$membership);
			$playlist_count = count($membership['Playlist']);
		}
		
		// Total Access
		array_push($access_array, $video_count);
		$access_count = array_sum($access_array);
		//$this->savefield('access_count',$access_count);

		$q = "update clients_users set access_count = $access_count, playlist_count = $playlist_count, video_count = $video_count where id = $membership_id";
		
		return $this->query($q);
		
		
	}

	
	
	/* SEARCH PLUGIN CODE */
	


    //public $hasAndBelongsToMany = array('Tag' => array('with' => 'Tagged'));

    public $filterArgs = array(
    	array(
    	'name' => 'search', 
    	 'type' => 'query',
    	 'method' => 'nameSearch'
    	),
    	array(
    	'name' => 'name', 
    	 'type' => 'query',
    	 'method' => 'orConditions'
    	),
    	array(
    	'name' => 'email', 
    	'type' => 'like',
    	'field' => 'User.username',
    	),
    	 array(
    	'name' => 'user_type', 
    	'type' => 'value',
    	'field' => 'user_type_id',
    	),
    	array(
    	'name' => 'company', 
    	 'type' => 'query',
    	 'method' => 'companySearch'
    	),
    	array(
    	'name' => 'city', 
    	 'type' => 'query',
    	 'method' => 'citySearch'
    	),
    	array(
    	'name' => 'state', 
    	 'type' => 'query',
    	 'method' => 'stateSearch'
    	),
    	array(
    	'name' => 'zip', 
    	 'type' => 'query',
    	 'method' => 'zipSearch'
    	),
    	array(
    	'name' => 'country', 
    	 'type' => 'query',
    	 'method' => 'countrySearch'
    	),
    );


    public function nameSearch($data = array()) {
        $filter = $data['search'];
        $cond = array(
            'OR' => array(
			'User.name LIKE' => '%'.$filter.'%',
			'User.first_name LIKE' => '%' . $filter . '%',
			'User.last_name LIKE' => '%' . $filter . '%',
        		'User.username LIKE' => '%' . $filter . '%',
            ));
        return $cond;
    }

    public function companySearch($data = array()) {
        $filter = $data['company'];
        $cond = array(
            'OR' => array(
			'User.company LIKE' => '%'.$filter.'%',
            ));
        return $cond;
    }
    
    public function citySearch($data = array()) {
        $filter = $data['city'];
        $cond = array(
            'OR' => array(
			'User.city LIKE' => '%'.$filter.'%',
            ));
        return $cond;
    }
    
    public function stateSearch($data = array()) {
        $filter = $data['state'];
        $cond = array(
            'OR' => array(
			'User.state LIKE' => '%'.$filter.'%',
            ));
        return $cond;
    }
    
    public function zipSearch($data = array()) {
        $filter = $data['zip'];
        $cond = array(
            'OR' => array(
			'User.zip LIKE' => '%'.$filter.'%',
            ));
        return $cond;
    }
    
    public function countrySearch($data = array()) {
        $filter = $data['country'];
        $cond = array(
            'OR' => array(
			'User.country LIKE' => '%'.$filter.'%',
            ));
        return $cond;
    }
	
    public function orConditions($data = array()) {
        $filter = $data['name'];
        $cond = array(
            'OR' => array(
                'User.first_name LIKE' => '%' . $filter . '%',
                'User.last_name LIKE' => '%' . $filter . '%',
            ));
        return $cond;
    }
	
    
    function getPlaylistIds($membership_id)
    {
    	
    }
    
    function getVideotIds($membership_id)
    {
    
    }
    
	/* END SEARCH PLUGIN CODE */
	
}
?>
