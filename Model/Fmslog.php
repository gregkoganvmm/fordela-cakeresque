<?php 
class Fmslog extends AppModel {
	
	var $useTable = 'fmslog';
	
	
	/*
	*
	*
	*/
	function parse($fh){
		$ClientArray = array();
		$row = 1;
		while (($data = fgetcsv($fh, 0, "\t")) !== FALSE) {
			// Count fields in a row
			$num = count($data);
	
			$ColumnNames = array();
			//$cols=explode(' ', $data[0]);
			$cols=array(
			//Cloudfront log columns
			//http://docs.amazonwebservices.com/AmazonCloudFront/latest/DeveloperGuide/AccessLogs.html#LogFileFormat
				'date',
				'time',
				'x-edge-location',
				'c-ip',
				'x-event',
				'sc-bytes',
				'x-cf-status',
				'x-cf-client-id',
				'cs-uri-stem',
				'cs-uri-query',
				'c-referrer',
				'x-page-url',
				'c-user-agent',
				'x-sname',
				'x-sname-query',
				'x-file-ext',
				'x-sid'
			);
			//array_shift($cols);
			$cols = array_flip($cols);
			//print_r($cols);
			//print_r($ColumnNames);
			$ColumnNames = $cols;
	
			// Create log rows
			$column = $ColumnNames['x-cf-client-id'];
			$d = $data[$column];
			if($d!='-')
			{
				$ClientArray[$d]['c-ip'] = $data[$ColumnNames['c-ip']];
				//if($data[$ColumnNames['x-file-name']]!='-') $ClientArray[$d]['x-file-name'] = $data[$ColumnNames['x-file-name']];
				if($data[$ColumnNames['x-sname']]!='-') $ClientArray[$d]['x-sname'] = $data[$ColumnNames['x-sname']];
				$ClientArray[$d]['log'][$data[$ColumnNames['x-event']]] = array(
			                'x-event' => $data[$ColumnNames['x-event']],
				//'x-category' => $data[$ColumnNames['x-category']],
			                'date' => $data[$ColumnNames['date']],
			                'time' => $data[$ColumnNames['time']],
				//'tz' => $data[$ColumnNames['tz']],
			                'timestamp' => strtotime($data[$ColumnNames['date']].' '.$data[$ColumnNames['time']]),
				//'x-duration' => $data[$ColumnNames['x-duration']],
			                'sc-bytes' => $data[$ColumnNames['sc-bytes']],
				//'sc-stream-bytes' => $data[$ColumnNames['sc-stream-bytes']],
				);
			}
			$row++;
		}
		//return $ClientArray;
		$i = 0;
		$j = 0;
		$query = "INSERT INTO fmslog () VALUES ";
		$query_values = '';
		$LogArray = array();
		foreach($ClientArray as $ck=>$cv){
			$c_client_id = $ck;
			$c_ip = $cv['c-ip'];
			//$c_ip_country = geoip_country_name_by_addr($gi, $cv['c-ip']);
			$c_ip_country = 'US';
			$x_file_name = isset($cv['x-file-name']) ? substr($cv['x-file-name'], 0, 60) : '-';
			$x_sname = isset($cv['x-sname']) ? $cv['x-sname'] : '-';
	
			$client_id = explode('/',$x_sname);
			$fordela_client_id = (!empty($client_id[0]) and is_numeric($client_id[0]))?$client_id[0]:0;
	
			$connect_timestamp = isset($cv['log']['connect']['timestamp']) ? $cv['log']['connect']['timestamp'] : 0;
			$disconnect_timestamp = isset($cv['log']['disconnect']['timestamp']) ? $cv['log']['disconnect']['timestamp'] : 0;
			$play_timestamp = isset($cv['log']['play']['timestamp']) ? $cv['log']['play']['timestamp'] : 0;
			$stop_timestamp = isset($cv['log']['stop']['timestamp']) ? $cv['log']['stop']['timestamp'] : 0;
			$pause_timestamp = isset($cv['log']['pause']['timestamp']) ? $cv['log']['pause']['timestamp'] : 0;
			$unpause_timestamp = isset($cv['log']['unpause']['timestamp']) ? $cv['log']['unpause']['timestamp'] : 0;
			$x_duration = isset($cv['log']['stop']['x-duration']) ? $cv['log']['stop']['x-duration'] : 0;
			$sc_bytes = (isset($cv['log']['disconnect']['sc-bytes']) AND isset($cv['log']['connect']['sc-bytes'])) ? $cv['log']['disconnect']['sc-bytes'] - $cv['log']['connect']['sc-bytes'] : 0;
			$sc_stream_bytes = (isset($cv['log']['play']['sc-stream-bytes']) AND isset($cv['log']['stop']['sc-stream-bytes'])) ? $cv['log']['stop']['sc-stream-bytes'] - $cv['log']['play']['sc-stream-bytes'] : 0;
	
			if($j>19 and !empty($query_values)){
				//$result = mysql_query($query.$query_values, $DB);
				$result = $this->query($query.$query_values);
				$query_values = '';
				$j = 0;
			}
			if($query_values) $query_values .= ', ';
			$query_values .= "(null, '$fordela_client_id', '$c_client_id', '$c_ip', '$c_ip_country', '$x_file_name', '$x_sname', $connect_timestamp, $disconnect_timestamp, $play_timestamp, $stop_timestamp, $pause_timestamp, $unpause_timestamp, $x_duration, $sc_bytes, $sc_stream_bytes)";
			$j++;
	
			$i++;
		}
		//echo $query_values;
		if(!empty($query_values)){
			$result = $this->query($query.$query_values);
		}else{
			//echo 'heay';
			return false;
		}
	
	}
	
	
	/*
	 * 
	 * 
	 */
	function o_parse($fh){
		$ClientArray = array();
		$row = 1;
		while (($data = fgetcsv($fh, 0, "\t")) !== FALSE) {
			// Count fields in a row
			$num = count($data);
				
			if($num>1 or substr($data[0], 0, 8)=='#Fields:'){
				// Column names
				//echo "<p> $num fields in line $row: <br /></p>\n";
				if(substr($data[0], 0, 8)=='#Fields:'){
					// Row of Column names
					$ColumnNames = array();
					$cols=explode(' ', $data[0]);
					array_shift($cols);
					$cols = array_flip($cols);
					//print_r($cols);
					//print_r($ColumnNames);
					$ColumnNames = $cols;
				}else{
					// Create log rows
					//$column = $ColumnNames['c-client-id'];
					//$d = $data[$column];
					$d = 0;
					if($d!='-'){
						$ClientArray[$d]['c-ip'] = $data[$ColumnNames['c-ip']];
						if($data[$ColumnNames['x-file-name']]!='-') $ClientArray[$d]['x-file-name'] = $data[$ColumnNames['x-file-name']];
						if($data[$ColumnNames['x-sname']]!='-') $ClientArray[$d]['x-sname'] = $data[$ColumnNames['x-sname']];
						$ClientArray[$d]['log'][$data[$ColumnNames['x-event']]] = array(
			                'x-event' => $data[$ColumnNames['x-event']],
			                'x-category' => $data[$ColumnNames['x-category']],
			                'date' => $data[$ColumnNames['date']],
			                'time' => $data[$ColumnNames['time']],
			                'tz' => $data[$ColumnNames['tz']],
			                'timestamp' => strtotime($data[$ColumnNames['date']].' '.$data[$ColumnNames['time']].' '.$data[$ColumnNames['tz']]),
			                'x-duration' => $data[$ColumnNames['x-duration']],
			                'sc-bytes' => $data[$ColumnNames['sc-bytes']],
			                'sc-stream-bytes' => $data[$ColumnNames['sc-stream-bytes']],
						);
					}
		
				}
				$row++;
			}
		}
		//return $ClientArray;
		$i = 0;
		$j = 0;
		$query = "INSERT INTO fmslog () VALUES ";
		$query_values = '';
		$LogArray = array();
		foreach($ClientArray as $ck=>$cv){
			$c_client_id = $ck;
			$c_ip = $cv['c-ip'];
			//$c_ip_country = geoip_country_name_by_addr($gi, $cv['c-ip']);
			$c_ip_country = 'US';
			$x_file_name = isset($cv['x-file-name']) ? substr($cv['x-file-name'], 0, 60) : '-';
			$x_sname = isset($cv['x-sname']) ? $cv['x-sname'] : '-';
		
			$client_id = explode('/',$x_sname);
			$fordela_client_id = (!empty($client_id[0]) and is_numeric($client_id[0]))?$client_id[0]:0;
		
			$connect_timestamp = isset($cv['log']['connect']['timestamp']) ? $cv['log']['connect']['timestamp'] : 0;
			$disconnect_timestamp = isset($cv['log']['disconnect']['timestamp']) ? $cv['log']['disconnect']['timestamp'] : 0;
			$play_timestamp = isset($cv['log']['play']['timestamp']) ? $cv['log']['play']['timestamp'] : 0;
			$stop_timestamp = isset($cv['log']['stop']['timestamp']) ? $cv['log']['stop']['timestamp'] : 0;
			$pause_timestamp = isset($cv['log']['pause']['timestamp']) ? $cv['log']['pause']['timestamp'] : 0;
			$unpause_timestamp = isset($cv['log']['unpause']['timestamp']) ? $cv['log']['unpause']['timestamp'] : 0;
			$x_duration = isset($cv['log']['stop']['x-duration']) ? $cv['log']['stop']['x-duration'] : 0;
			$sc_bytes = (isset($cv['log']['disconnect']['sc-bytes']) AND isset($cv['log']['connect']['sc-bytes'])) ? $cv['log']['disconnect']['sc-bytes'] - $cv['log']['connect']['sc-bytes'] : 0;
			$sc_stream_bytes = (isset($cv['log']['play']['sc-stream-bytes']) AND isset($cv['log']['stop']['sc-stream-bytes'])) ? $cv['log']['stop']['sc-stream-bytes'] - $cv['log']['play']['sc-stream-bytes'] : 0;
		
			if($j>19 and !empty($query_values)){
				//$result = mysql_query($query.$query_values, $DB);
				$result = $this->query($query.$query_values);
				$query_values = '';
				$j = 0;
			}
			if($query_values) $query_values .= ', ';
			$query_values .= "(null, $fordela_client_id, $c_client_id, '$c_ip', '$c_ip_country', '$x_file_name', '$x_sname', $connect_timestamp, $disconnect_timestamp, $play_timestamp, $stop_timestamp, $pause_timestamp, $unpause_timestamp, $x_duration, $sc_bytes, $sc_stream_bytes)";
				$j++;
		
			$i++;
		}
		echo($query_values);
		if(!empty($query_values)){
			$result = $this->query($query.$query_values);
			var_dump($result);
		}else{
			//echo 'heay';
			return false;
		}
		
	}
	
}

