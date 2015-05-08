<?php 
class Annotation extends AppModel {

	var $name = 'Annotation';
	
	var $validate = array(
		'note' => array(
			'notempty' => array(
				'rule' => array('notempty'),
				'message'=>'Annotations can not be empty'
				),
		)
	);
	
	function _createAnnotation($playlist_id = null, $note = null){
		
		$this->create();
		$this->data['Annotation']['playlist_id'] = $playlist_id;
		$this->data['Annotation']['note'] = $note;
		
	  	if($this->save($this->data))
  		{
  			return $this->id;
  		} else {
  			return false;
  		}
	}
}
?>