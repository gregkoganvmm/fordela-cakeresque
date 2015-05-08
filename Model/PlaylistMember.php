<?php 
/**
 * Short description for playlist_member.php
 *
 * Long description for playlist_member.php
 *
 * $Id: playlist_member.php 4029 2010-08-18 22:38:02Z daniel $
 *
 * @package       vms
 * @subpackage    vms.app.models
 * @since         v 1.0
 */
/**
 * PlaylistMember class
 *
 * @uses          AppModel
 * @package       vms
 * @subpackage    vms.app.models
 */
class PlaylistMember extends AppModel  { 

	var $name = 'PlaylistMember';

	var $belongsTo = array(
		'Playlist'=>array(
			'counterCache' =>'member_count',		  
		),
		'Membership' => array(
			'className' => 'Membership',
			'foreignKey' => 'member_id',
			'counterCache' => 'playlist_count',
			//'counterScope' => array('Playlist.active' => 1),
		)
	);
	
	
	/**
	*
	* @param $user_id
	* @param $playlist_id
	*/
	function createPlaylistMembership($member_id,$playlist_id,$user_id,$client_id = null){
		$this->create();
		$this->data['member_id'] = $member_id;
		$this->data['playlist_id'] = $playlist_id;
		$this->data['user_id'] = $user_id;
		if($this->save($this->data)){
			$this->User = ClassRegistry::init('User');
			if(!$this->User->existsInClient($user_id,$client_id)){
				$this->User->addToClient($user_id,$client_id);
			}
			
			return $this->id;
		}else{
			return false;
		}
	}
	 
} 
?>