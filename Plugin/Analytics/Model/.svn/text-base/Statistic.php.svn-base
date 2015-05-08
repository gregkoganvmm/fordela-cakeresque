<?php
class Statistic extends AnalyticsAppModel {

	
	/**
	 * BelongsTo Association
	 * 
	 * @var array $belongsTo Association
	 */
	var $belongsTo = array(
		'User' => array(
			'className'  => 'User',
			'conditions' => '',
			'order'      => '',
			'foreignKey' => 'user_id'
    	),
		'Video' => array(
			'className'  => 'Video',
			'conditions' => '',
			'order'      => '',
			'foreignKey' => 'video_id'
    	),
		'Playlist' => array(
			'className'  => 'Playlist',
			'conditions' => '',
			'order'      => '',
			'foreignKey' => 'playlist_id'
		),
		'StatisticType' => array(
			'className'  => 'StatisticType',
			'conditions' => '',
			'order'      => '',
			'foreignKey' => 'statistic_type_id'
		)
		
	);
	/**
	 * 
	 * Activity By Day
	 * Returns Analytics activities grouped by day.
	 * 
	 * @param string $activity
	 * @param string $startDate
	 * @param string $endDate
	 * @param int $client_id
	 * @param string $model 
	 * @param int $model_id
	 * @return array
	 */
	
	function activityByDay($activity,$startDate,$endDate,$client_id = 0,$model=null,$model_id=null){
		$dateConditions = 'WHERE Statistic.created < "'.$endDate.'" AND Statistic.created > "'.$startDate.'"';
		$modelConditions = '';
		if($client_id != 0){
			$modelConditions .=" AND Video.client_id = $client_id";
		}
		switch($activity){
			case 'logins':
				$modelConditions .= "AND Statistic.statistic_type_id = 'Logon'";
				//$modelConditions .= "AND Statistic.attendtype = 'Logon'";
				break;
			case 'plays':
			default:
				$modelConditions .= "AND Statistic.statistic_type_id = 'Play Video'";
				//$modelConditions .= "AND Attendance.attendtype = 'Play Video'";
		}
		switch(strtolower($model)){
			case 'video':
				$modelConditions .=" AND Statistic.video_id = $model_id";
				break;
			case 'user':
				$modelConditions .=" AND Statistic.user_id = $model_id";
				break;
			case 'playlist':
				$modelConditions .=" AND Statistic.playlist_id = $model_id";
				break;
			case 'client':
			default:
		}

		$query  = "
		SELECT 
			COUNT(*) as count, 
			DATE_FORMAT(created,'%m-%d') as created
		FROM 
			statistics Statistic
			$dateConditions
			$modelConditions
		GROUP BY 
			YEAR(created), 
			MONTH(created), 
			DAY(created);";
		return $this->query($query);
	}
	
	
	/**
	 * 
	 * @param $startDate
	 * @param $endDate
	 * @param $limit
	 * @param $model
	 * @param $model_id
	 * @param $client_id
	 * 
	 * @return array
	 */
	
	function topvideos($startDate,$endDate,$limit=5,$model=null, $model_id = null, $client_id = 0){
		$modelConditions = '';
		
		if($client_id != 0){
			$modelConditions .=" AND Video.client_id = $client_id";
		}
		
		switch(strtolower($model)){
			case 'user':
				$modelConditions .=" AND Statistic.user_id = $model_id";
				break;
			case 'playlist':
				$modelConditions .=" AND Statistic.playlist_id = $model_id";
				break;
			case 'client':
			default:
	
		}
		
		
		
		$dateConditions = 'WHERE Statistic.created < "'.$endDate.'" AND Statistic.created > "'.$startDate.'"';
		$query = "
			SELECT 
				Count(*) as count,
				Statistic.video_id,
				Statistic.created,
				Video.id, 
				Video.title,
				Video.client_id
			FROM 
				 statistics AS Statistic
			LEFT JOIN 
				videos AS Video 
			ON 
				(Statistic.video_id = Video.id)
				$dateConditions
				$modelConditions
			AND Statistic.statistic_type_id = 2
			GROUP BY 
				video_id
			ORDER BY 
				count DESC
			LIMIT 
				$limit
		";
				//debug($query);
		return $this->query($query);
	}
	
	
	/**
	 * 
	 * @param $activity
	 * @param $startDate
	 * @param $endDate
	 * @param $client_id
	 * @param $model
	 * @param $model_id
	 * 
	 * @return array
	 */
	function activities_by_day($activity,$startDate,$endDate,$client_id = 0,$model=null,$model_id=null){
		$dateConditions = 'WHERE Statistic.created < "'.$endDate.'" AND Statistic.created > "'.$startDate.'"';
		$modelConditions = '';
		//$groupingConditions = '`Statistic`.`statistic_type_id`,';
		
		if($client_id != 0 && $activity != 'logins'){
			$modelConditions .=" AND Video.client_id = $client_id";
		}
		
		switch($activity){
			case 'logins': 	
				$modelConditions .= " AND Statistic.statistic_type_id = 1";	
				$from = 'FROM statistics Statistic';
				break;
			case 'play25': 	
				$modelConditions .= " AND Statistic.statistic_type_id = 21";	
				break;
			case 'play50': 	
				$modelConditions .= " AND Statistic.statistic_type_id = 18";	
				break;
			case 'play75': 	
				$modelConditions .= " AND Statistic.statistic_type_id = 19";	
				break;
			case 'play100': 	
				$modelConditions .= " AND Statistic.statistic_type_id = 20";	
				break;
			case 'plays':
				$modelConditions .= " AND Statistic.statistic_type_id = 2";
				break;
			default:
				//$modelConditions .= " AND Statistic.statistic_type_id = 2";
		}
		
		switch(strtolower($model)){
			case 'video':
				if($model_id){
					$modelConditions .=" AND Statistic.video_id = $model_id";	
				}
				
				break;
			case 'user':
				$modelConditions .=" AND Statistic.user_id = $model_id";
				break;
			case 'playlist':
				$modelConditions .=" AND Statistic.playlist_id = $model_id";
				break;
			case 'client':
			default:
	
		}
		if(empty($from)){
			$from = 'FROM 
				statistics Statistic
						LEFT JOIN 
				videos AS Video 
			ON 
				(Statistic.video_id = Video.id)';
		}
		
//		debug($modelConditions);
		

		$query  = "
		
		
		SELECT 
			COUNT(*) as count, 
			Statistic.statistic_type_id as statistic_type_id,
			DATE_FORMAT(`Statistic`.`created`,'%m-%d') as created
			

			$from
			
			
			$dateConditions
			$modelConditions
			
		GROUP BY

			YEAR(`Statistic`.`created`), 
			MONTH(`Statistic`.`created`), 
			DAY(`Statistic`.`created`);";

		return $this->query($query);
	}
	
	/**
	 * 
	 * @param unknown_type $startDate
	 * @param unknown_type $endDate
	 * @param unknown_type $method
	 * @return array
	 */
	function loginsDaily($startDate,$endDate,$client_id = 0,$model=null,$model_id=null){
		$modelConditions = '';
		
		if($client_id != 0){
			$modelConditions .=" AND User.client_id = $client_id";
		}
		
		
		switch(strtolower($model)){
			case 'video':
				$modelConditions .=" AND Statistic.video_id = $model_id";
				break;
			case 'user':
				$modelConditions .=" AND Statistic.user_id = $model_id";
				break;
			case 'playlist':
				$modelConditions .=" AND Statistic.playlist_id = $model_id";
				break;
			case 'client':
			default:
	
		}
		
		
		//$thirtyDaysAgo = date('Y-m-d 00:00:00',time()-(30*DAY));
		$dateConditions = 'WHERE Statistic.created < "'.$endDate.'" AND Statistic.created > "'.$startDate.'"';
		//$dateConditions = 'WHERE AD.date < "'.$endDate.'" AND AD.date > "'.$startDate.'"';
		$query  = "
		
		
		SELECT 
			COUNT(*) as count, 
			DATE_FORMAT(`Statistic`.`created`,'%m-%d') as created,
			User.client_id
			

		FROM 
			statistics Statistic
		
		LEFT JOIN 
			users AS User 
		ON 
			(Statistic.user_id = User.id)

			
			
			
			$dateConditions
			$modelConditions
		
						
		AND
			Statistic.statistic_type_id = 1
		GROUP BY 
			YEAR(`Statistic`.`created`), 
			MONTH(`Statistic`.`created`), 
			DAY(`Statistic`.`created`);";

		return $this->query($query);
	}
	
	/**
	 * 
	 * @param $user_id
	 * @param $client_id
	 */
	function getVideosWatched($user_id,$client_id = null,$get_created = false){
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
	 * @param $user_id
	 * @param $client_id
	 */
	function getVideosNotWatched($user_id,$client_id = null){
		$this->bindModel(array('belongsTo'=>array('Video')));
//		$this->bindModel(array('belongsTo'=>array('PlaylistItem')));
		/* Test */
		$conditions = array(
//		'NOT' => array(
//			'Statistic.statistic_type_id'=>21, // 25%
//			'Statistic.statistic_type_id'=>18, // 50%
//			'Statistic.statistic_type_id'=>19, // 75%
//			'Statistic.statistic_type_id'=>20, // 100%
//			),
			'Statistic.user_id'=>$user_id, // current user
			'Video.client_id'=>$client_id, // current client
//		'AND' => array(
//			'PlaylistItem.playlist_id'=>$playlist_id
//			)
		);
		$unwatched = $this->find('all',array('conditions'=>$conditions,'fields'=>array('DISTINCT Statistic.video_id')));
		
		return $unwatched;
		
	}
	
	
}?>