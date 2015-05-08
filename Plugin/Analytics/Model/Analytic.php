<?php
define('ANALYTICS_EVENT_PLAY','Play');
define('ANALYTICS_EVENT_RESUME','Resume');
define('ANALYTICS_EVENT_CLOSE','Close');
define('ANALYTICS_EVENT_PAUSE','Pause');
define('ANALYTICS_EVENT_FULLSCREEN_ON','Fullscreen');
define('ANALYTICS_EVENT_FULLSCREEN_OFF','Fullscreen Off');
define('ANALYTICS_EVENT_PLAY_COMPLETE','Complete');
define('ANALYTICS_EVENT_SEEK','Seek');

class Analytic extends AppModel
{
 	var $name = 'Analytic';
    var $primaryKey = '_id';
    var $useDbConfig = 'mongo';
    var $useTable = 'statistics';
    

 	
    function schema() {
        $this->_schema = array(
		'_id' => array('type' => 'integer', 'primary' => true, 'length' => 40),
		//'title' => array('type' => 'string'),
		//'body' => array('type' => 'text'),
		'attendtype' => array('type' => 'string'),
		'statistic_type_id' => array('type' => 'integer'),
		'user_id' => array('type' => 'integer'),
		'playlist_id' => array('type' => 'integer'),
		'video_id' => array('type' => 'integer'),
		'value' => array('type' => 'integer'),
		'playlist_item_id' => array('type' => 'integer'),
		'embed_url' => array('type' => 'string'),
		'remote_address' => array('type' => 'string'),
		'ip_address' => array('type' => 'string'),        
		'created'=>array('type'=>'datetime'),
        'country_name'=>array('type'=>'string')
			
        );
        return $this->_schema;
    }
    

    /**
     * 
     * Enter description here ...
     * @param unknown_type $client_id
     * @param unknown_type $type
     * @param unknown_type $start_date
     * @param unknown_type $end_date
     * @param unknown_type $limit
     */
	function top($client_id, $type = 'video', $start_date = null, $end_date = null, $limit = 10)
	{
		$m = new Mongo(MONGO_DB_HOST);
		$db = $m->vms;

		// Determines which field to key on.. video_id, playlist_id, user_id
		$keys = array($type.'_id' => 1);
		
		// Set initial values
		$initial = array("count" => 0);
		
		// Javascript function to perform
		$reduce = "function (obj, prev) { prev.count++; }";
		
		// Conditions is the 4th optional arg of group()
		$conditions = array('client_id'=>(int)$client_id);
		
		if($start_date!=null)
		{
			
		}
		
		if($end_date!=null)
		{
			
		}
		
		$cursor = $db->selectCollection('session_activities')->group($keys, $initial, $reduce, $conditions);
    	
    	$top = array();
    	
    	$cursor = $cursor['retval'];
    	$cursor = Set::sort($cursor, '{n}.count', 'desc');
    	$i=0;
    	foreach($cursor as $key => $document){
    		$top[] = $document;
    		$i++;
    		if($i >= $limit){ break; } // limit to $limit
    	}
		return $top;
	}
    
    
    
    
    
    /**
     * 
     * Enter description here ...
     * @param unknown_type $start_date
     * @param unknown_type $end_date
     */
    function videoPlaysByDate($client_id,$start_date=null,$end_date=null)
    {
    
    	$key = 'Client'.$client_id.'s'.$start_date.'e'.$end_date;
        $m = new Mongo(MONGO_DB_HOST);
    	$db = $m->vms;
    	$map = new MongoCode("function() {
			day = Date.UTC(this.created.getFullYear(), this.created.getMonth(), this.created.getDate());
  			this.activity.forEach(function(a){
				if(a.name=='Play'){
					emit(day, {count: 1});
				}
			});
			//emit({day: day}, {count: 1});
		}");

    	$reduce = new MongoCode("function(key, values) {
  			var count = 0;
  			values.forEach(function(v) {
				count += v['count'];
  			});
  			return {count: count};
		}");
    		
		
		
	$start = new MongoDate(strtotime(date("Y-m-d", strtotime($start_date)) . " -3 days"));
	$end = new MongoDate(strtotime(date("Y-m-d", strtotime($end_date)) . " +3 days"));	
	
    	$plays = $db->command(array(
    	    'mapreduce' => 'session_activities', 
    	    'map' => $map,
    	    'reduce' => $reduce,
    	    'query' => array(
		'activity.name' =>  array(
		    	    		//'$eq' => ANALYTICS_EVENT_PLAY,
					'$exists' => true,
		    	    	),
    	    	//'activity.name' => ANALYTICS_EVENT_PLAY,
    		'client_id'=>(int)$client_id,
    	    	'created'=>array(	
    	    		'$gte'=>$start,
			'$lte'=>$end 
    	    	)
    	    ),
    	    'out' => $key
    	    )
    	);
	
    	//debug($plays);
    	$videoPlays = $db->selectCollection($plays['result'])->find();
	
	$videoPlaysByDate = array();
	$videoPlaysByDate = iterator_to_array($videoPlays);
	//date_default_timezone_set('UTC');

	foreach($videoPlaysByDate as $i=>$play){
		//debug($play);
		$videoPlaysByDate[$i]['date'] = gmdate('Y-m-d',$play['_id']/1000); //uses gmdate to deal with UTC date
		$videoPlaysByDate[$i]['value'] = $play['value']['count'];
	}
	
	$videoPlaysByDate = $this->_fillInDates($videoPlaysByDate, $start_date, $end_date);

	return $videoPlaysByDate;
	
	}
	
	
	/**
	*
	* Enter description here ...
	* @param unknown_type $start_date
	* @param unknown_type $end_date
	*/
	function videoPlaysByDateAndPlaylist($playlist_id,$start_date=null,$end_date=null)
	{
	
		$m = new Mongo(MONGO_DB_HOST);
		$db = $m->vms;
		$map = new MongoCode("function() {
				day = Date.UTC(this.created.getFullYear(), this.created.getMonth(), this.created.getDate());
				this.activity.forEach(function(a){
					if(a.name=='Play'){
						emit(day, {count: 1});
					}
				});
			}");
		 
		$reduce = new MongoCode("function(key, values) {
	  			var count = 0;
	  			values.forEach(function(v) {
					count += v['count'];
	  			});
	  			return {count: count};
			}");
		 
		$start = new MongoDate(strtotime(date("Y-m-d", strtotime($start_date)) . " -3 days"));
		$end = new MongoDate(strtotime(date("Y-m-d", strtotime($end_date)) . " +3 days"));	
		 
		$plays = $db->command(array(
	    	    'mapreduce' => 'session_activities', 
	    	    'map' => $map,
	    	    'reduce' => $reduce,
	    	    'query' => array(
	    	    	'activity.name' => ANALYTICS_EVENT_PLAY,
	    			'playlist_id'=>(int)$playlist_id,
	    	    	'created'=>array(
    		    		'$gte'=>$start,
    				'$lte'=>$end,
			       	)
			),
	    	    'out' => 'playCountsByPlaylist'.$playlist_id));
		 
		 
		//debug($plays);
		$videoPlays = $db->selectCollection($plays['result'])->find();
		 
		$videoPlaysByDate = array();
		$i=0;
		foreach($videoPlays as $play){
			//debug($play);
			$videoPlaysByDate[$i]['date'] = gmdate('Y-m-d',$play['_id']/1000);
			$videoPlaysByDate[$i]['value'] = $play['value']['count'];
			$i++;
		}
		$videoPlaysByDate = $this->_fillInDates($videoPlaysByDate, $start_date, $end_date);
		return $videoPlaysByDate;
	}
	
	/**
	*
	* Enter description here ...
	* @param unknown_type $start_date
	* @param unknown_type $end_date
	*/
	function videoPlaysByDateAndUser($user_id,$start_date=null,$end_date=null)
	{
	
		$m = new Mongo(MONGO_DB_HOST);
		$db = $m->vms;
		$map = new MongoCode("function() {
					day = Date.UTC(this.created.getFullYear(), this.created.getMonth(), this.created.getDate());
		  			emit({day: day,foo:'bar'}, {count: 1});
				}");
			
		$reduce = new MongoCode("function(key, values) {
		  			var count = 0;
		  			values.forEach(function(v) {
						count += v['count'];
		  			});
		  			return {count: count};
				}");
		
		$start = new MongoDate(strtotime(date("Y-m-d", strtotime($start_date)) . " -3 days"));
		$end = new MongoDate(strtotime(date("Y-m-d", strtotime($end_date)) . " +3 days"));
			
		$plays = $db->command(array(
			'mapreduce' => 'session_activities', 
			'map' => $map,
			'reduce' => $reduce,
			'query' => array(
			    'activity.name' => ANALYTICS_EVENT_PLAY,
				    'user_id'=>(int)$user_id,
			    'created'=>array(
				    '$gte'=>$start,
				    '$lte'=>$end,
			    )
			    
			),
			'out' => 'playCountsByUser'.$user_id));
			
			
		//debug($plays);
		$videoPlays = $db->selectCollection($plays['result'])->find();
			
		$videoPlaysByDate = array();
		$i=0;
		foreach($videoPlays as $play){
			//debug($play);
			$videoPlaysByDate[$i]['date'] = gmdate('Y-m-d',$play['_id']['day']/1000);
			$videoPlaysByDate[$i]['value'] = $play['value']['count'];
			$i++;
		}
		$videoPlaysByDate = $this->_fillInDates($videoPlaysByDate, $start_date, $end_date);
		return $videoPlaysByDate;
	}
	
	
	/**
	*
	* Enter description here ...
	* @param unknown_type $start_date
	* @param unknown_type $end_date
	*/
	function playsByDateAndVideo($video_id,$start_date=null,$end_date=null)
	{
		//debug(func_get_args());
		$m = new Mongo(MONGO_DB_HOST);
		$key = 'Video'.$video_id.'s'.$start_date.'e'.$end_date;
		$db = $m->vms;
		$map = new MongoCode("function() {
					day = Date.UTC(this.created.getFullYear(), this.created.getMonth(), this.created.getDate());
					this.activity.forEach(function(a){
						if(a.name=='".ANALYTICS_EVENT_PLAY."'){
							emit(day, {count: 1});
						}
					});
				}");
			
		$reduce = new MongoCode("function(key, values) {
		  			var count = 0;
		  			values.forEach(function(v) {
						count += v['count'];
		  			});
		  			return {count: count};
				}");
		
		$start = new MongoDate(strtotime(date("Y-m-d", strtotime($start_date)) . " -3 days"));
		$end = new MongoDate(strtotime(date("Y-m-d", strtotime($end_date)) . " +3 days"));
			
		$plays = $db->command(array(
			'mapreduce' => 'session_activities', 
			'map' => $map,
			'reduce' => $reduce,
			'query' => array(
				'created' => array(
					'$exists' => true, 
				),
				'activity.name' =>  array(
					'$exists' => true,
				),
				'video_id'=>(int)$video_id,
				'created'=>array(
					'$gte'=>$start,
					'$lte'=>$end
				)
			),
			'out' => $key
			)
		);
			
			
		$videoPlaysByDate = array();
		
		//var_dump($plays);
		if(!empty($plays['result'])){
			$videoPlays = $db->selectCollection($plays['result'])->find();
			//var_dump($videoPlays);
			
			$videoPlaysByDate = iterator_to_array($videoPlays);
			
			
			foreach($videoPlaysByDate as $i=>$play){
				//debug($play);
				$videoPlaysByDate[$i]['date'] = gmdate('Y-m-d',$play['_id']/1000);
				$videoPlaysByDate[$i]['value'] = $play['value']['count'];
			
			}
			
			$videoPlaysByDate = $this->_fillInDates($videoPlaysByDate, $start_date, $end_date);
			
		}
		return $videoPlaysByDate;
	}
	
	
	/**
	*
	* We may be able to delete this function now.  Using playsByCountry() instead
	* and passing in filter with whatever id.
	* @param unknown_type $start_date
	* @param unknown_type $end_date
	*/
	function videoPlaysByCountry($client_id,$start_date=null,$end_date=null)
	{
		
		$SA = ClassRegistry::init('Analytics.SessionActivity');
		
		$total_plays = $SA->find('count', array(
						'conditions' => array(
							'client_id'=>(int)$client_id,
							'activity.name'=>ANALYTICS_EVENT_PLAY,
							'created'=>array(
								'$gte'=>new MongoDate(strtotime($start_date)),
								'$lte'=>new MongoDate(strtotime($end_date.'+1 day'))
							)
						),
					));
		
		$fields = array('country_name');
		$conditions = array(
			'client_id'=>(int)$client_id,
			'activity.name'=>ANALYTICS_EVENT_PLAY,
			'country_name' =>  array('$exists' => true),
			'created'=>array(
				'$gte'=>new MongoDate(strtotime($start_date)),
				'$lte'=>new MongoDate(strtotime($end_date.'+1 day'))
			)
		);
		$total_plays_country = $SA->find('all',compact('conditions'));
		
		return $this->_format_country_array($total_plays, $total_plays_country);
	}
	
	/**
	 * 
	 * Used from video_view and playlist_view
	 * @param array $filter
	 * @param date $start_date
	 * @param date $end_date
	 */
	function playsByCountry($filter = array(),$start_date=null,$end_date=null) {
		$SA = ClassRegistry::init('Analytics.SessionActivity');
		$fields = array('country_name');
		$default_conditions = array(
			'activity.name'=>ANALYTICS_EVENT_PLAY,
			'created'=>array(
				'$gte'=>new MongoDate(strtotime($start_date)),
				'$lte'=>new MongoDate(strtotime($end_date.'+1 day'))
			)
		);
		$country_exists = array('country_name' =>  array('$exists' => true));
		// merge video_id or playlist_id into conditions
		$tp_conditions = array_merge($default_conditions, $filter); 
		// merge country_name
		$country_conditions = array_merge($tp_conditions,$country_exists);
		
		$total_plays = $SA->find('count', array('conditions' => $tp_conditions));
		$total_plays_country = $SA->find('all', array('conditions' => $country_conditions,'fields' => $fields));
		
		//debug($total_plays);
		//debug($total_plays_country);
		//die;
		
		return $this->_format_country_array($total_plays, $total_plays_country);
	}
	
	/**
	*
	* @param $user_id
	* @param $client_id
	*/
	function getVideosWatched($membership_id,$get_created = false){
		
		$SA = ClassRegistry::init('Analytics.SessionActivity');
		$fields = array('video_id','activity','playthrough');
		$conditions = array(
			'membership_id'=>(int)$membership_id,
			'playthrough'=>(int)100
		);
		$r = $SA->find('all',compact('conditions','fields'));
		$w = Set::extract('/SessionActivity/video_id',$r);
		$userWatchedVideos = array_unique($w);
		$userWatchedVideos = array_values($userWatchedVideos);
		
		return $userWatchedVideos;
	}
	
	
	
	/**
	*
	* @param $user_id
	* @param $client_id
	*/
	function old_getVideosWatched($user_id,$client_id = null,$get_created = false){
		$this->bindModel(array('belongsTo'=>array('Video')));
		$conditions = array(
				'Statistic.statistic_type_id'=>21, // 25%
				'Statistic.statistic_type_id'=>18, // 50%
				'Statistic.statistic_type_id'=>19, // 75%
				'Statistic.statistic_type_id'=>20, // 100%
				'Statistic.user_id'=>$user_id, // current user
				'Video.client_id'=>$client_id, // current client
		);
		if($get_created == true){
			$watched = $this->find('all',array('conditions'=>$conditions,'fields'=>array('Statistic.video_id','Statistic.created')));
		}else{
			$watched = $this->find('all',array('conditions'=>$conditions,'fields'=>array('DISTINCT Statistic.video_id')));
		}
	
		return $watched;
	}
	
	
	
	
	/**
	 * 
	 * @param unknown_type $data
	 * @param unknown_type $startDate
	 * @param unknown_type $endDate
	 * @return array
	 */
	function _fillInDates($data,$startDate,$endDate){

		$day = $startTime = strtotime($startDate.' 00:00:00');
	  	$endTime = strtotime($endDate.' 23:59:59');
	  	
	  	$days = range($startTime,$endTime,DAY);
		
	  	
	  	$days = array();
		while ( $day <= $endTime ) {
			$days[] = date('Y-m-d',$day);
			$day += DAY; //DAY is a cake shortcut definition
		}
		
		$results = array();
		$j=0;
	
		foreach($days as $date){
			$found = false;
			foreach($data as $item){
				if($item['date']==$date){
					$results[$j] = $item;
					$found = true;		
					break;
				}
			}
			if(!$found){
				$results[$j] = array('date'=>$date,'value'=>0); 
			}
			$j++;
		}
		return $results;
	}
	
	/**
	*
	* Get total PlayStarts for a membership 
	* @param $membership_id
	*/
	function getPlayStarts($membership_id){
		
		$SA = ClassRegistry::init('Analytics.SessionActivity');
		$fields = array('video_id','activity');
		$conditions = array(
			'membership_id'=>(int)$membership_id,
			'activity.name'=>ANALYTICS_EVENT_PLAY
		);
		$play_count = $SA->find('count',compact('conditions','fields'));

		return $play_count;
	}
	
	/**
	 * subfunction of what would be duplicated code in videoPlaysByCountry()
	 * and playsByCountry
	 */
	function _format_country_array($total_plays, $total_plays_country){
		$videoPlaysByCountry = array();				
		if(!empty($total_plays_country)){
			$countryStringOnly = Set::extract('{n}.SessionActivity.country_name',$total_plays_country);
			array_walk_recursive($countryStringOnly, "Analytic::replacer"); // Because array_count_values is integer & string only
			$country_counts = array_count_values($countryStringOnly);
			$null_count = (isset($country_counts['null'])) ? $country_counts['null']: 0; // At this point null values already converted to 'N/A'
			unset($country_counts['null']);
			
			// reformat array for graph and count country total
			$c = 0;
			foreach($country_counts as $k => $cc){
				$c = $c + $cc;
				if($k <> 'N/A'){
					$videoPlaysByCountry[] = array($k, $cc);
				}
			}
			// Merge unknown locations
			$na_count = ($total_plays - $c) + $null_count;
			if($na_count > 0){
				$na = array(array("N/A",$na_count));
				$videoPlaysByCountry = array_merge($videoPlaysByCountry, $na);
				//$videoPlaysByCountry['N/A'] = $na_count;
			}
			$videoPlaysByCountry = Set::sort($videoPlaysByCountry, '{n}.1','desc');
			
			return $videoPlaysByCountry;
		}else{
			return array(array('N/A',$total_plays)); // all plays are unknown
		}
	}
	/**
	 * 
	 * Used by array_walk_recursive()
	 * @param unknown_type $item
	 * @param unknown_type $key
	 */
	function replacer(& $item, $key) {
	    if ($item === null) {
	        $item = 'null';
	    }
	}
}