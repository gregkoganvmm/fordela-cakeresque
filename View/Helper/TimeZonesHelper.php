<?php 
/**
 * Short description for time_zones.php
 * A Helper that handles time zones
 *
 * Long description for time_zones.php
 *
 * $Id: time_zones.php 13590 2012-03-19 18:02:53Z zack $ 
 * 
 * @package       vms
 * @since         v 1.0
 */
/**
 * TimeZonesHelper class
 *
 * @uses          
 * @package       vms
 */
class TimeZonesHelper extends AppHelper {

	/**
	 * helpers
	 * @var unknown_type
	 */
	 var $helpers = array('Auth');
	 
	 var $options = Array(
		'zone' => null,
		'singleline' => false,
		'dateonly' => false,
		'format' => null	 
	 );
	
	var $javascript_dates = array(
	 				'n/j/y g:i a' => 'm/d/yy',
	   				'j/n/y g:i a' => 'd/m/yy', 
	   				'j/n/Y g:i a' => 'd/m/yyyy',     
	   				'n/j/Y g:i a' => 'm/d/yyyy'
	 
	 	);
	
	
	 var $json_dates = array(
	 				'n/j/y g:i a' => 'm/d/y',
	   				'j/n/y g:i a' => 'd/m/y', 
	   				'j/n/Y g:i a' => 'd/m/yy',     
	   				'n/j/Y g:i a' => 'm/d/yy'
	 
	 	);
	 
	 var $dates = array(                     
  		 		'n/j/y g:i a' => 'M/D/YY', 
  				'j/n/y g:i a' => 'D/M/YY', 
  				'j/n/Y g:i a' => 'D/M/YYYY',     
  				'n/j/Y g:i a' => 'M/D/YYYY'
 		);
	 
	/**
	 * returns an array of timezones
	 * 
	 * @return Ambigous <string, multitype:string > 
	 */	
	function show() {
  		$zones = array(
  			'Pacific/Apia'             => 'Apia, Upolu, Samoa',                      // UTC-11:00
  			'US/Hawaii'                => 'Honolulu, Oahu, Hawaii, United States',   // UTC-10:00
  			'US/Alaska'                => 'Anchorage, Alaska, United States',        // UTC-09:00
  			'US/Pacific'               => 'Los Angeles, California, United States',  // UTC-08:00
 			'US/Mountain'              => 'Phoenix, Arizona, United States',         // UTC-07:00
 			'US/Central'               => 'Chicago, Illinois, United States',        // UTC-06:00
 			'US/Eastern'               => 'New York City, United States',            // UTC-05:00
 			'America/Santiago'         => 'Santiago, Chile',                         // UTC-04:00
 			'America/Sao_Paulo'        => 'Ṣo Paulo, Brazil',                      // UTC-03:00
 			'Atlantic/South_Georgia'   => 'South Georgia, S. Sandwich Islands',      // UTC-02:00
 			'Atlantic/Cape_Verde'      => 'Praia, Cape Verde',                       // UTC-01:00
 			'Europe/London'            => 'London, United Kingdom',                  // UTC+00:00
 			'UTC'                      => 'Universal Coordinated Time (UTC)',        // UTC+00:00
 			'Europe/Paris'             => 'Paris, France',                           // UTC+01:00
 			'Africa/Cairo'             => 'Cairo, Egypt',                            // UTC+02:00
 			'Europe/Moscow'            => 'Moscow, Russia',                          // UTC+03:00
 			'Asia/Dubai'               => 'Dubai, United Arab Emirates',             // UTC+04:00
 			'Asia/Karachi'             => 'Karachi, Pakistan',                       // UTC+05:00
 			'Asia/Dhaka'               => 'Dhaka, Bangladesh',                       // UTC+06:00
 			'Asia/Jakarta'             => 'Jakarta, Indonesia',                      // UTC+07:00
 			'Asia/Hong_Kong'           => 'Hong Kong, China',                        // UTC+08:00
 			'Asia/Tokyo'               => 'Tokyo, Japan',                            // UTC+09:00
 			'Australia/Sydney'         => 'Sydney, Australia',                       // UTC+10:00
 			'Pacific/Noumea'           => 'Noum̩a, New Caledonia, France'           // UTC+11:00
 		);
 		$dateTime = new DateTime('now');
 		foreach($zones as $zone => $name) {
 			$zoneObject = new DateTimeZone($zone);
 			$dateTime->setTimezone($zoneObject);
 			$zones[$zone] = $dateTime->format('g:i A - ').$name;
 		}
 		return $zones;
 	}
 	
 	/**
 	 * converts a timestamp to timzone
 	 * 
 	 * @param timestamp $time the time to convert
 	 * @param array $options array of options for display:
 	 * 				string $zone the timezone to convert time to default uses User Timezone (example: US/Pacific)
 	 * 				Bool $singleline if the time is returned on one line or two, default is on two
 	 *				Bool $dateonly if true will show date only not time
 	 *				string $format format of date default (MDY)
 	 * 
 	 * @return timestamp $zonetime the converted timestamp 
 	 */
	function display($time,$options=null) {
		if(is_array($options)){
			$options = array_merge($this->options,$options);
		}
		else{
			$options = $this->options;
		}
		extract($options);
		$zoneTime = null;
		
		if(!empty($time)){
			$user_timezone = $this->Auth->user('timezone');
			if(empty($zone) && !is_array($user_timezone)){
				$zone = $user_timezone;
			}
			else{
				$zone = 'US/Pacific';
			}
			//Format to display
			if(empty($format)){
				if(is_array($this->Auth->user('date_format'))){
					$format = 'n/j/y g:i a';
				}
				else{
					$format = $this->Auth->user('date_format');
					// The following would use Client date_format instead of $this->Auth->user('date_format')
					// Not being used yet.
					//$this->Client = ClassRegistry::init('Client');
					//$this->Client->id = $this->Auth->user('client_id');
					//$format = $this->Client->field('date_format');
				}
			}
			if($format == 'n/Y'){
				$time = '01/'.$time;
			}

			elseif(strpos($format, 'j') == 0 && strpos($time, '/') != -1){
				//Format to day month year to conver into datetime object
				$time_array = explode('/', $time );
				if(!empty($time_array[1])){
					$time = $time_array[1].'/'.$time_array[0].'/'.$time_array[2];
				}
			}
			$dateTime = new DateTime($time);
			if(!empty($zone)){
				$zoneObject = new DateTimeZone($zone);
				$dateTime->setTimezone($zoneObject);
			}
			if($dateonly){
				//split format to remove time
				$formats = explode(' ', $format);
				$zoneTime = $dateTime->format($formats[0]);
			}
			elseif($singleline){
				$zoneTime = $dateTime->format($format);
			}
			else{
				//split format to <br /> time
				$formats = explode(' ', $format);
				$zoneTime = $dateTime->format($formats[0]).
						'<br />'.
				$dateTime->format($formats[1]. ' '.$formats[2]);
			}
		}
		//debug($zoneTime);
		return $zoneTime;
	}	
	
	
	function dateFormats(){
  		
		$dates = $this->dates;
 		$dateformat = $this->Auth->user('date_format');
 		if(!empty($dateformat))
 		{
 			foreach($dates as $key => $value)
 			{
 				if($key == $dateformat)
 				{
 					$date_array[$key]=$value;
 				}
 			}
 			
 			foreach($dates as $key=>$value)
 			{
 				if($key != $dateformat)
 				{
 					$date_array[$key]=$value;	
 				}
 			}
 			return $date_array;
 		}
 	
 		return $dates;
	}
	
	
	
	
	
}
?>