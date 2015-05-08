<?php 
class Media extends AppModel {
	
	var $useTable = false;
	var $_schema = array(
        'item_id'		=>array('type'=>'integer', 'key'=>'primary'),
		'active'		=>array('type'=>'BOOL', 'length'=>1),
	 	'title'		=>array('type'=>'string', 'length'=>100),
		'status'		=>array('type'=>'string', 'length'=>100), 
        'description'=>array('type'=>'text'),
		'type'=>array('type'=>'string', 'length'=>100),
	 	'controller'=>array('type'=>'string', 'length'=>100),
	 	'client'=>array('type'=>'string', 'length'=>100),
	 	'thumbnail'=>array('type'=>'string', 'length'=>255),
		'runtime'=>array('type'=>'string', 'length'=>255),
        'created'=>array('type'=>'datetime','null'=>1),
	 	'modified'=>array('type'=>'datetime','null'=>1),
		'created_by'=>array('type'=>'integer'),
		'uploader'=>array('type'=>'string', 'length'=>255),
		'link'=>array('type'=>'string', 'length'=>500),		
		'filename'=>array('type'=>'string', 'length'=>500),
    );
	
	
	function paginate($conditions, $fields, $order, $limit, $page = null, $recursive = null, $extra = array()) {
		//debug(func_get_args());
		if(isset($conditions['Media.type'])){
			$type = $conditions['Media.type'];
			unset($conditions['Media.type']);
		}else{
			$type = false;
		}
		$conditions_str = $this->_paginateConditions($conditions);

		$order_str = $this->_paginateOrder($order);
		//debug($order_str);
		//$order_str = '';
		$limit_str = max($limit*($page-1),0); 
		$limit_str .= ', '.$limit;
		
		$videosql ="
			           SELECT `Media`.id as item_id,
			           		`Media`.active,
			           		`Status`.name AS status, 
                            `Media`.title, 
                            `Media`.description,
                            'Video' as type,
                            'videos' as controller,
                            `Client`.name AS client,
                            `Media`.image1 as thumbnail,
			    `Media`.runtime AS runtime,
			    `Media`.archive AS filename,
                            `Media`.created,
                            `Media`.modified,
                            `Media`.created_by,
                            `User`.name AS uploader, 
                            CONCAT('/videos/preview/', `Media`.id) AS link
			           FROM videos AS `Media`
			           INNER JOIN clients AS `Client` on `Media`.client_id = `Client`.id
			           INNER JOIN status AS `Status` on `Media`.status_id = `Status`.id
			           INNER JOIN users AS `User` on `Media`.created_by = `User`.id 
			           $conditions_str
			           ";
		$audiosql ="
			           SELECT `Media`.id as item_id,
			           		`Media`.active,
			           		`Status`.name AS status,  
                            `Media`.title as title,
                            `Media`.description,
                            'Audio' as type,
                            'audio' as controller,
                            `Client`.name AS client,
                            `Media`.thumbnail,
			    `Media`.length AS runtime,
			    `Media`.filename,			    
                            `Media`.created,
                            `Media`.modified,
                            `Media`.created_by,
                            `User`.name AS uploader,
                            CONCAT('/audio/preview/', `Media`.id) AS link
			           FROM audio AS `Media`
			           INNER JOIN clients AS `Client` on `Media`.client_id = `Client`.id
			           INNER JOIN status AS `Status` on 3 = `Status`.id
			           INNER JOIN users AS `User` on `Media`.created_by = `User`.id 
			           $conditions_str
			           ";
		$imagesql ="	          
			           SELECT `Media`.id as item_id,
			           		`Media`.active,
			           		`Status`.name AS status, 
                            `Media`.title, 
                            `Media`.description,
                            'Image' as type,
                            'images' as controller,
                            `Client`.name AS client,
                            `Media`.thumbnail,
			    'N/A' as runtime,
			    `Media`.filename,
                            `Media`.created,
                            `Media`.modified,
                            `Media`.created_by,
                            `User`.name AS uploader,
                            CONCAT('".VMS_PROTOCOL."://".IMG_SERVER."/img/images/', `Client`.id, '_', `Media`.filename) AS link
			           FROM images AS `Media`
			           INNER JOIN clients AS `Client` on `Media`.client_id = `Client`.id
			           INNER JOIN status AS `Status` on 3 = `Status`.id
			           INNER JOIN users AS `User` on `Media`.created_by = `User`.id 
			            $conditions_str
			           ";
		$orderLimit = " 
			         ORDER BY $order_str
			         LIMIT $limit_str";
		if($type and in_array($type,array('video','image','audio'))){
			//debug($type);
			$sql = ${$type.'sql'}; 
		}else{
			$sql = '('.$videosql.') UNION ('.$audiosql.') UNION ('.$imagesql.')';
		}
		$sql = $sql.$orderLimit;
		
		$results = $this->query($sql);
		
		if($type){
			foreach($results as $k=>$item){
				$results[$k]['Media'] = array_merge($results[$k]['Media'], $results[$k][0],$results[$k]['Status'],$results[$k]['Client'],$results[$k]['User']);
				unset($results[$k][0],$results[$k]['Status'],$results[$k]['Client'],$results[$k]['User']);
//				unset($results[$k][0]); 
			}
		}else{
			foreach($results as $k=>$item){
				$results[$k]['Media'] = $results[$k][0];
				unset($results[$k][0]); 
			}
		}
		if(!$results){
			return array();
		}else{
			return $results;	
		}
	}
	
	
	
	/**
	 * 
	 * @param $conditions
	 * @param $recursive
	 * @param $extra
	 */
	function paginateCount($conditions = null, $recursive = 0, $extra = array()) {
		if(isset($conditions['Media.type'])){
			$type = $conditions['Media.type'];
			unset($conditions['Media.type']);
		}else{
			$type = false;
		}

		$conditions_str = $this->_paginateConditions($conditions);
		
		
		if($type and in_array($type,array('video','image','audio'))){
			
			switch($type){
				case 'video':
					$sql = "SELECT COUNT(*) AS count FROM videos AS Media $conditions_str";
					break;
				case 'audio':
					$sql = "SELECT COUNT(*) AS count FROM audio AS Media $conditions_str";
					break;
				case 'image':
					$sql = "SELECT COUNT(*) AS count FROM images AS Media $conditions_str";
					break;
			}
			$mediaCount = $this->query($sql);
			$total = $mediaCount[0][0]['count'];
			
		}else{
			$sql = "SELECT COUNT(*) AS count FROM videos AS Media $conditions_str";
			$videosCount = $this->query($sql);
	
			$sql = "SELECT COUNT(*) AS count FROM audio AS Media $conditions_str";
			$audioCount = $this->query($sql);
	
			$sql = "SELECT COUNT(*) AS count FROM images AS Media $conditions_str";
			$imagesCount = $this->query($sql);

			$total = $videosCount[0][0]['count'] + $audioCount[0][0]['count'] + $imagesCount[0][0]['count'];
			
		}
		//debug($total);
		
		return $total;
	}
	
	/**
	 * 
	 * @param $conditions
	 */
	function _paginateConditions($conditions=array()){
        if(isset($conditions['Media.client_id'])){
        	$client_id = $conditions['Media.client_id'];
        	unset($conditions['Media.client_id']);
        } else{
        	$client_id = 0;
        }
        
        $conditions_str = 'WHERE `Media`.deleted = 0 AND `Media`.active = 1 AND `Media`.client_id = '.$client_id;
        $j = 0;
        foreach($conditions as $field=>$condition){
        	//if($j>0) 
        	$conditions_str .= ' AND ';
        	if(strstr($field,' ')){
        		$conditions_str .= "$field $condition";
        	}else{
        		$conditions_str .= "$field = '$condition'";
        	}
        	$j++;
        }
        

        return $conditions_str;
    
	}
	
	/**
	 * 
	 * @param array $order
	 */
	function _paginateOrder($order=array()){
		//debug($order);
		if(!empty($order)){
			//$out = 'ORDER BY ';
			if(is_array($order)){
				$out = '';
				$i=0;
				foreach($order as $k => $v){
					if($i!=0){
						$out = ', ';	
					}
					$out = str_replace('Media.','',$k).' '.strtoupper($v);
					$i++;
				}
				return $out;
			}else{
				$out = $order;
			}
			return $out;
		}else{
			return 'created DESC';
		}
		
	}
}
?>