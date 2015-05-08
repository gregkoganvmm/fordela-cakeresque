<?php
/**
 * Short description for playlist_item.php
 *
 * Long description for playlist_item.php
 *
 * @package       vms
 * @subpackage    vms.app.models
 * @since         v 1.0
 */
/**
 * PlaylistItem class
 *
 * @uses          AppModel
 * @package       vms
 * @subpackage    vms.app.models
 */

class PlaylistItem extends AppModel {

	var $name = 'PlaylistItem';
	
	var $actsAs = array(
		//'Polymorphic'=>array('classField' => 'model'),
		//'Utils.List' => array('positionColumn' => 'weight','scope' => 'playlist_id')
	);
	
	var $_activeVideoConditions = array(
		'Video.deleted'=>0,
		'Video.active'=>1,
		'Video.primary_version_id IS NOT NULL'
	);
	
	var $belongsTo = array('Playlist' =>array(
		'className'    => 'Playlist',
		'conditions'   => '',
		'order'        => '',
		'foreignKey'   => 'playlist_id',
		'counterCache' => 'video_count'
		),
	);
		
	/**
  	 * 
  	 * @param $video_id
   	 * @param $playlist_id
  	 */
	function defaultRecord($video_id,$playlist_id){
		
		$PlaylistItem['PlaylistItem']['model']='Video';
		$PlaylistItem['PlaylistItem']['foreign_id']=$video_id;
		$PlaylistItem['PlaylistItem']['playlist_id']=$playlist_id;
		return $PlaylistItem;
	}
	
	
	/*
	 * 
			$this->set('activePlaylistItems',$this->PlaylistItem->findAll('PlaylistItem.playlist_id="'.$id.'" AND PlaylistItem.duration > 0 AND PlaylistItem.end_date > "'.$now.'"  AND Video.deleted=0 AND Video.primary_version_id IS NOT NULL', null, 'PlaylistItem.weight ASC'));
			$this->set('expiredPlaylistItems',$this->PlaylistItem->findAll('PlaylistItem.playlist_id="'.$id.'" AND PlaylistItem.duration > 0 AND PlaylistItem.end_date < "'.$now.'"  AND Video.deleted=0 AND Video.primary_version_id IS NOT NULL', null, 'PlaylistItem.end_date ASC'));
			$this->set('inactivePlaylistItems',$this->PlaylistItem->findAll('PlaylistItem.playlist_id="'.$id.'" AND PlaylistItem.duration = 0 AND Video.deleted=0 AND Video.primary_version_id IS NOT NULL ', null, 'Video.title ASC'));

	 * 
	 */
	
	
	/**
	 * 
	 * Enter description here ...
	 * @param unknown_type $options
	 */
	function __findActiveItems($options=array()){
		
		
		/*
		 * PlaylistItem.playlist_id="'.$id.'" 
		 * AND PlaylistItem.duration > 0 
		 * AND PlaylistItem.end_date > "'.$now.'"  
		 * AND Video.deleted=0 
		 * AND Video.primary_version_id IS NOT NULL', null, 'PlaylistItem.weight ASC'));
		 * 
		 */
		
		$conditions['PlaylistItem.playlist_id'] = $options['playlist_id'];
		$conditions['PlaylistItem.duration >'] =  0;
		$conditions['PlaylistItem.end_date >'] = NOW; 
		
		$conditions = array_merge($this->_activeVideoConditions,$conditions);
		$order = 'PlaylistItem.weight ASC';
		
		return $this->find('all', compact('conditions','order'));
	}

	/**
	 * 
	 * Enter description here ...
	 * @param unknown_type $options
	 */
	function __findExpiredItems($options=array()){
		
		/**
			PlaylistItem.playlist_id="'.$id.'" 
			AND PlaylistItem.duration > 0 
			AND PlaylistItem.end_date < "'.$now.'"  
			AND Video.deleted=0 
			AND Video.primary_version_id IS NOT NULL', null, 'PlaylistItem.end_date ASC
		 */
		$conditions['PlaylistItem.playlist_id'] = $options['playlist_id'];
		$conditions['PlaylistItem.duration >'] =  0;
		$conditions['PlaylistItem.end_date <'] = NOW; 
		
		$conditions = array_merge($this->_activeVideoConditions,$conditions);
		$order = 'PlaylistItem.weight ASC';
		
		return $this->find('all', compact('conditions','order'));
		
	}
	
	/**
	 * 
	 * Enter description here ...
	 * @param unknown_type $options
	 */
	function __findInactiveItems($options=array()){
		/*
		 * PlaylistItem.playlist_id="'.$id.'" 
		 * AND PlaylistItem.duration = 0 
		 * AND Video.deleted=0 
		 * AND Video.primary_version_id IS NOT NULL 
		 */
		
		//find active videos to remove from list
		$activeVideoListConditions['PlaylistItem.playlist_id'] = $options['playlist_id'];
		$activeVideoListConditions['PlaylistItem.duration >'] =  0;
		$activeVideoListConditions['PlaylistItem.end_date >'] = NOW; 
		
		$activeVideoIds = 
			array_keys(
				$this->find('list',array(
					'conditions'=>$activeVideoListConditions,
					'fields'=>'PlaylistItem.video_id,PlaylistItem.id'
				)
			)
		); 
		
		//find expired videos to remove from list
		$expiredVideoListConditions['PlaylistItem.playlist_id'] = $options['playlist_id'];
		$expiredVideoListConditions['PlaylistItem.duration >'] =  0;
		$expiredVideoListConditions['PlaylistItem.end_date <'] = NOW;
		
		$expiredVideoIds = 
			array_keys(
				$this->find('list',array(
					'conditions'=>$expiredVideoListConditions,
					'fields'=>'PlaylistItem.video_id,PlaylistItem.id'
				)
			)
		); 

		if(!empty($options['user_id'])){
			$conditions['Video.created_by'] = $options['user_id'];	
		}
		
		$conditions['Video.client_id'] = $options['client_id'];
		$conditions['NOT']['Video.id'] = array_merge($activeVideoIds,$expiredVideoIds);

		
		$conditions = array_merge($this->_activeVideoConditions,$conditions);

		/*
		$this->Behaviors->attach('Containable');
		return $videos = $this->find('all', array(
				'conditions'=>$conditions,
				'contain'=>array(
					'Video' => array(
						'VideoVersion',
						'PrimaryVersion',
					)
				)
			)
		);
		*/
		$recursive = 1;
		return $this->Video->find('all', compact('conditions','recursive'));
		
	}
	
	function __findPlaylists($id) {
		$playlists = $this->find('list',array(
			'conditions'=>array('PlaylistItem.version_id'=>$id),
			'fields'=>array('PlaylistItem.playlist_id'),
		));
		return $playlists;
	}
	
	/*
	 * Bulk delete from PlaylistItems
	 * $foreign_id represents either $video_id or $audio_id being passed from the
	 * video or audio controller.
	 */
	function removeItemFromPlaylists($foreign_id,$model = 'Video'){
		return $this->deleteAll(array('PlaylistItem.foreign_id'=>$foreign_id,'PlaylistItem.model'=>$model));
	}
	
}
?>
