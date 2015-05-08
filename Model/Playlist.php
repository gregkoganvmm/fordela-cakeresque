<?php 
/**
 * Short description for playlist.php
 *
 * Long description for playlist.php
 *
 * $Id: playlist.php 4029 2010-08-18 22:38:02Z daniel $
 *
 * @package       vms
 * @subpackage    vms.app.models
 * @since         v 1.0
 */
/**
 * Playlist class
 *
 * @uses          AppModel
 * @package       vms
 * @subpackage    vms.app.models
 */

class Playlist extends AppModel  { 

	var $name = 'Playlist';

	var $belongsTo = array('Client' =>array('className'    => 'Client',
		'conditions'   => '',
		'order'        => '',
		'foreignKey'   => 'client_id',
		'counterCache' => true,
		'counterScope' => array('Playlist.active' => 1),
		),
	);

	var $hasAndBelongsToMany = array(
	
		'User' => array(
			'classname' => 'User',
			'order' => 'User.name'
		),
		'Membership' => array(
			'with'=>'PlaylistMember',
			'associationForeignKey' => 'member_id',
			//'order' => 'User.name'
		)
	);
	/**
	 * 
	 * @var unknown_type
	 */
	var $actsAs = array(
	//	'Metadata.Metadatable',
		
		'Containable'
	);
	
	
	/**
	 * 
	 * For finds using Polymorphic
	 * @var unknown_type
	 */
    
	var $hasMany = array(
        'PlaylistItem' => array(
            'className' => 'PlaylistItem',    
            //'conditions' => array('PlaylistItem.type' => 'video'),
            'dependent' => true
        ),
 //       'MetadataValue'=>array('foreignKey'=>'foreign_key','conditions'=>array('MetadataValue.model'=>'Playlist'))
    );
    

    /**
     * Returns Active Items in A playlist time window or all items if $active = false
     * 
     * Enter description here ...
     * @param int $playlist_id The Playlist
     * @param boolean $active 
     */
    
    
    function getItems($playlist_id,$active = true,$options=array()){
	$default_options = array(
				'order'=>array('PlaylistItem.weight ASC'),
				'limit'=>null,
				'conditions'=>array(),
				'contain'=>array(),
				'fields'=>array(),
				'polyConditions'=>array()
			);
	$options = array_merge($default_options,$options);
	
    	$now = date('Y-m-d');
	
    	$playlist = $this->read('type,expiration_type,start_date,end_date',$playlist_id);
		//Playlist Items
		if(($active == true) AND ($playlist['Playlist']['expiration_type'] == 'playlist_item')) {
			$conditions = array(
				'PlaylistItem.playlist_id'=>$playlist_id,
				"(PlaylistItem.start_date <='$now' OR PlaylistItem.start_date IS NULL)",
				"(PlaylistItem.end_date >='$now' OR PlaylistItem.end_date IS NULL)",
			);
		}
		//Playlist
		elseif($active == true && $playlist['Playlist']['expiration_type'] == 'playlist' && !empty($playlist['Playlist']['end_date']) && !empty($playlist['Playlist']['end_date'])){
			//Not an expired playlist
			if($playlist['Playlist']['end_date'] >= $now && $playlist['Playlist']['start_date'] <= $now){
				$conditions = array(
					'PlaylistItem.playlist_id'=>$playlist_id,
				);	
			}
			else{
				return array();
				//$conditions = array(
				//	'PlaylistItem.playlist_id'=>$playlist_id,
				//);
			}
		}
		//Anything Else
		else {
			$conditions = array(
				'PlaylistItem.playlist_id'=>$playlist_id,
			);
		}
	
		switch($playlist['Playlist']['type']){
			case 'Video':
				$this->Video = ClassRegistry::init('Video');
				$this->Video->bindModel(array('hasMany'=>array('MetadataValue'=>array('foreignKey'=>'foreign_id','condtions'=>'MetadataValue.model="Video"'))));
				
				$polyConditions = array(
					'Video'=>array(
					'contain'=>array(
								'VideoVersion(id,name,dir,filename,host,encoding_profile_id,format,url,bitrate,created,ext,filesize)',
								'PrimaryVersion(id,name,dir,filename,host,encoding_profile_id,format,url)',
								'Attachment'=>array(
									'conditions'=>array('model_type'=>'Video'),
									'order'=>array(
										'Attachment.created'=>'desc'
									),
									'fields'=>array('filename','description','id','name','mimetype'),
								),
								'MetadataValue(key,model,val)',
								
								//'MetadataValue'=>array('fields'=>array('key','model','val')),
							)
					)
			);
			break;
			
			case 'Audio':
				$this->Audio = ClassRegistry::init('Audio');
				$this->Audio->bindModel(array('hasMany'=>array('MetadataValue'=>array('foreignKey'=>'foreign_id','condtions'=>'MetadataValue.model="Audio"'))));
				
				$polyConditions = array(
					'Audio'=>array(
					'contain'=>array(
								'Album',
								'MetadataValue',
						)
					)
			);
			break;
			
			default:
						
		}
   		$conditions = array_merge($conditions,$options['conditions']);
		$polyConditions = array_merge($polyConditions,$options['polyConditions']);

   		return $this->PlaylistItem->find('all', array(
				'contain'=>$options['contain'],
				'fields'=>$options['fields'],
				'conditions'=>$conditions,
				'polyConditions'=>$polyConditions,
				'order'=>$options['order'],
				'limit'=>$options['limit']
			)
		);
    }
    
    function getActiveItems($playlist_id){
    	$now = date('Y-m-d');
    	$playlist = $this->read('type,expiration_type',$playlist_id);
    	
    	if($playlist['Playlist']['expiration_type'] == 'playlist' || $playlist['Playlist']['expiration_type'] == 'none'){
    		$conditions = array(
    			'PlaylistItem.playlist_id'=>$playlist_id,
    		);
    	}else{
    		// Playlist.expiration_type = playlist_items
	    	$conditions = array(
	    		'PlaylistItem.playlist_id'=>$playlist_id,
	    		'PlaylistItem.start_date <'=>$now,
	    		'PlaylistItem.end_date >'=>$now,
	    	);
    	}

    	
    	$active_items = $this->PlaylistItem->find('all',array(
				'conditions'=>$conditions,
				'fields'=>array('PlaylistItem.foreign_id'),
		));
    	return $active = Set::extract('/PlaylistItem/foreign_id',$active_items);
    }
    
    function getExpiredItems($playlist_id){
    	$now = date('Y-m-d');
    	
    	$playlist = $this->read('type,expiration_type',$playlist_id);
    	
    	$conditions = array(
			'PlaylistItem.playlist_id'=>$playlist_id,
			'PlaylistItem.model'=>$playlist['Playlist']['type'],
			'PlaylistItem.end_date <'=>$now,
    	);
    	
    	$expired_items = $this->PlaylistItem->find('all',array(
				'conditions'=>$conditions,
				'fields'=>array('PlaylistItem.foreign_id'),
		));
		return $expired = Set::extract('/PlaylistItem/foreign_id',$expired_items);
		
    }

  
	/**
	 * 
	 * @param $client_id
	 */
	
	function defaultRecord($client_id=null){
		$playlist['Playlist']['name'] = 'Example Playlist';
		$playlist['Playlist']['client_id'] = $client_id;
		$playlist['Playlist']['type'] = 'Video';
		$playlist['Playlist']['annotations_email'] = $this->Client->field('email', array('id'=>$client_id));
		$playlist['Playlist']['active'] = 1;
		$playlist['Playlist']['format'] = 'Template';
		$playlist['Playlist']['template'] = 'theater';
		$playlist['Playlist']['annotations'] = 1;
		$playlist['Playlist']['logo'] = 'Acme.png';
		$playlist['Playlist']['status_id'] = 5;
		$playlist['Playlist']['delivery_format'] = 'streaming';
		return $playlist;
	}
	
	/**
	 * Moves an image from one folder into another
	 * Currently moves to the maped img server
	 * @param $newfilename
	 * @param $fileSrc
	 */
	function uploadLogo($newfilename,$fileSrc){
		$newFilePath = IMG_UPLOAD_ROOT.$newfilename;
		if (copy($fileSrc,$newFilePath)){			
			return true;
		}
		return false;
	}
	
	
		/* SEARCH PLUGIN CODE */
	
	public $filterArgs = array(
    	array(
    	'name' => 'search_primary_user', 
    	 'type' => 'query',
    	 'method' => 'primaryUserSearch'
    	),
    	array(
    	'name' => 'search_name', 
    	'type' => 'query',
    	'method' => 'nameSearch'
    	),
    	array(
    	'name' => 'search_type', 
    	'type' => 'query',
    	'method' => 'typeSearch'
    	),
    	array(
    	'name' => 'search_start_date', 
    	'type' => 'query',
    	'method' => 'dateSearch'
    	),
		array(
    	'name' => 'search_end_date', 
    	'type' => 'query',
    	'method' => 'dateSearch'
		),
    	array(
    	'name' => 'metadata', 
    	'type' => 'query',
    	'method' => 'metadataSearch'
    	),
		array(
		'name' => 'id_search',
		'type' => 'query',
			    	 'method' => 'idSearch'
		),
		array(
			'name' => 'client_id_search',
		    'type' => 'query',
			'method' => 'ClientIdSearch'
		)
    );
    
	public function clientIdSearch($data = array()) {
		$filter = $data['client_id_search'];
		$cond = array(
		                'Playlist.client_id' => $filter
		);
		return $cond;
	}
	
	public function idSearch($data = array()) {
		$filter = $data['id_search'];
		$cond = array(
		                'Playlist.id' => $filter
		);
		return $cond;
	}
	
    public function primaryUserSearch($data = array()) {
        $filter = $data['search_primary_user'];
        $cond = array(
                'Playlist.primary_user_id' => $filter,
	);
        return $cond;
    }
	
 	public function nameSearch($data = array()) {
        $filter = $data['search_name'];
        $cond = array(
                'Playlist.name LIKE' => '%' . $filter . '%',
            );
        return $cond;
    }
    
	public function typeSearch($data = array()) {
        $filter = $data['search_type'];
        $cond = array(
                'Playlist.type' => $filter,
            );
        return $cond;
    }
    
 	public function dateSearch($data = array()) {
 		$cond = array();
 		if(isset($data['search_start_date'])){
 			$start = $data['search_start_date'];
 			$cond['Playlist.start_date >='] = $start;
 		}
 		if(isset($data['search_end_date'])){
 			$end =  $data['search_end_date'];
 			$cond['Playlist.end_date <='] = $end;
 			
 		}

        return $cond;
    }
    
	public function metadataSearch($datas = array()) {
 		//cut out extra data
		unset($datas['metadata']);
 		unset($datas['search_name']);
 		unset($datas['search_start_date']);
 		unset($datas['search_end_date']);
 		//remove pagination calls
 		unset($datas['page']);
 		unset($datas['sort']);
 		unset($datas['direction']);
 		unset($datas['show']);
 		if(!empty($datas)){			
	    	$client_id = $_SESSION['Auth']['User']['client_id'];
	    	$media =array();
	        //pass in client idea and a keyed array with [metadatafield.label]=>metadatavalue.val
	        $i = 0;	
		        foreach($datas as $k => $data){
		        	//TODO This may cause issues if someone uses '|' in metadata, will see if i can find a better solution
		        	$isdate = strpos($data, '|');
 		        	if($isdate || !empty($isdate)){
		        		$data = str_replace('|', ',', $data);
 		        		$data = serialize($data);
		        	}
		        	$assets[$i] = $this->getAssets($client_id,$k,$data);
		        	if($i>0){
		        		$media = array_intersect($assets[$i], $assets[$i-1]);
		        	}
		        	else{
		        		$media = $assets[$i];
		        	}
		        	$i++;
		        } 
	        $cond = array(
	            'Playlist.id' => $media
	        );
	        return $cond;
 		}
    }
    
	/* END SEARCH PLUGIN CODE */
	/**
	 * Deletes the playlist assests
	 * Metadata,playlist_items,playlist_memberships
	 *
	 *
	 **/
	function delete_assets($id){
		$message = array();
		$this->id = $id;
		$name = $this->field('name');
		if($this->savefield('active',0)) {
			$message['text'] = $name.' was deleted';
			$message['status'] = 'success';
			//DELETE metadata_values
			$this->query("DELETE FROM `metadata_values` WHERE `foreign_id` = '$id' AND `model` = 'Playlist'");
			//DELETE playlist_members
			$this->query("DELETE FROM `playlist_members` WHERE `playlist_id` = '$id'");
			//DELETE playlist_items
			$this->query("DELETE FROM `playlist_items` WHERE `playlist_id` = '$id'");
		}
		else{
			$message['text'] = $name.' was not deleted please try again';
			$message['status'] = 'error';
		}
		return $message;
	} 
}
	
?>