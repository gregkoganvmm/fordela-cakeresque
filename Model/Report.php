<?php
/**
 
 *
 * @uses          AppModel
 * @package       vms
 * @subpackage    vms.app.models
 */
class Report extends AppModel {

		 function recordSet($data, $clientId){
		 		
		 		
		 					$results = array();
						 	$startDate = isset($data['Reports']['StartDate']['year'])?$data['Reports']['StartDate']['year'].'-'.$data['Reports']['StartDate']['month'].'-'.$data['Reports']['StartDate']['day']:'';
							$endDate = isset($data['Reports']['EndDate']['year'])?$data['Reports']['EndDate']['year'].'-'.$data['Reports']['EndDate']['month'].'-'.$data['Reports']['EndDate']['day']:'';
						 	$airline = isset($data['Reports']['airLine'])?$data['Reports']['airLine']:'';
						 	$conditions = array('client_id'=>$clientId);
						 	$additionalString = " ";
						 	$reports = $this->find('all',$conditions);	
						 	$query_string = $reports[0]['Report']['query']." AND playlists.client_id = $clientId AND (playlists.start_date BETWEEN '$startDate' AND  '$endDate')  GROUP BY audio.artist, audio.composer,audio.title,audio.label order by audio.artist;" ;
						 	//$this->log($query_string,"ReportQuery");
						 	if($data['Reports']['reportId']==1){
						 		$additionalString	= 	" AND `val` = '$airline';";
						 	}
						 		$results1 = $this->query($query_string);
								$query_string2 = $reports[1]['Report']['query']." $additionalString;";
								$this->log($query_string2,"ReportQuery");
								$results2 = $this->query($query_string2);
								$array_result = array(); 
								foreach($results1 as $result1){
										$playlist_id = $result1['playlists']['playlistID'];
									foreach($results2 as $result2){
										if(in_array($playlist_id, $result2['metadata_values'])){
											$array_result[] = array_merge($result1, $result2);
										}
									}										
							}
								
				return  $array_result;
			}
			
			
	function playlistRecordSet($clientId, $data){
		
		$conditions = array('client_id'=>$clientId);
		$startDate = isset($data['Reports']['StartDate']['year'])?$data['Reports']['StartDate']['year'].'-'.$data['Reports']['StartDate']['month'].'-'.$data['Reports']['StartDate']['day']:'';
		$endDate = isset($data['Reports']['EndDate']['year'])?$data['Reports']['EndDate']['year'].'-'.$data['Reports']['EndDate']['month'].'-'.$data['Reports']['EndDate']['day']:'';
		$reports = $this->find('all',$conditions);	
		$array_AP = array();
		$qry = $reports[3]['Report']['query']. " AND (playlists.start_date BETWEEN '$startDate' AND  '$endDate')" ;
		//$this->log($qry,"AsimQuery1");
		$qry2 = $reports[1]['Report']['query'].	" AND `val` = '".$data['Reports']['airLine']."'";
		$results1 = $this->query($qry);	
		$results2 = $this->query($qry2);
		
		  foreach($results1 as $result1){
			$playlist_id = $result1['playlists']['playlistID'];
					foreach($results2 as $result2){
								if(in_array($result1['playlists']['playlistID'], $result2['metadata_values'])){
									//debug($result1);	
									/*$array_AP[$result2['metadata_values']['val']][$result1['playlists']['playlistName']] = $result1['playlists']['playlistID'];*/
									$array_AP[$result2['metadata_values']['val']][] = $result1['playlists']['playlistID'];
									
								}
					}			
				
			}	
			return $array_AP;
	}
	
	function _returnHost($id)
	{
		$result = $this->query("select `val` from metadata_values where `key` = 'host' AND foreign_id = $id");
		$result2 = $this->query("select `name` from `playlists` where `id` =  $id");
		$host = '';
		$name = '';
		
		if(!empty($result))
		{
			$host = $result[0]['metadata_values']['val'];
		}
		if(!empty($result2))
		{
			$name = $result2[0]['playlists']['name'];
			
		}
		return array('host'=>$host,'name'=>$name);
		
	}
			
			function generateAirlineList()
			{
				
				$airlineQuery = 'Select * from metadata_fields where id = 2542'; 
				$airlines = $this->query($airlineQuery);
				if(!empty($airlines[0]['metadata_fields']['options'] )){
					return unserialize($airlines[0]['metadata_fields']['options']);
			}
				return false;
			}
			
			

		 
}
?>