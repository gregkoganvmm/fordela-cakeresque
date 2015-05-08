<?php
class Sess extends AppModel {
	var $name = 'Sess';
	var $primaryKey = '_id';
	var $useDbConfig = 'mongo';
	var $useTable = 'sess';
	
	var $mongoSchema = array(
		        'ip_address' => array('type' => 'string', 'length' => 40),
                'cake_session' =>array('type' => 'string', 'length' => 40),
                'client_id' => array('type' => 'integer'),
                'user_id' => array('type' => 'integer'),
                'membership_id'=> array('type' => 'integer'),
                'plays' => array('type' => 'integer'),
                'embed_url' => array('type' => 'string', 'length' => 40),
                'country_name' => array('type' => 'string', 'length' => 40),                   
                'country_code' => array('type' => 'string', 'length' => 40),
                'region' => array('type' => 'string', 'length' => 40),
                'city' => array('type' => 'string', 'length' => 40),
                'latitude' => array('type' => 'float'),
                'longitude' => array('type' => 'float'),
	        	'created'=>array('type'=>'datetime'),
			);

	function schema($key = null) {
		
		if(!empty($this->mongoSchema[$key])){
			return $this->mongoSchema[$key];
		}else{
			return $this->mongoSchema;
		}
		
	}
        
        /**
         * Makes a New Mongo DB Sess record with GeoIP data
         *
         **/
        function new_session($data){
            //get GeoIp Record
	    $ip_address = $_SERVER['SERVER_ADDR'];
  //          $ip_address = '63.245.165.0';
  	    $record = $this->_geo_ip($ip_address);

            $geoip = (array) $record;
            
            //remove extra data
            unset($geoip['country_code3']);
            unset($geoip['postal_code']);
            unset($geoip['area_code']);
            unset($geoip['dma_code']);
            unset($geoip['metro_code']);
            unset($geoip['continent_code']);
            
            if(is_numeric($geoip['region'])){
            	$geoip['region'] = geoip_region_name_by_code($geoip['country_code'],$geoip['region']);
            }
            
            
            
            $data = array_merge($data,$geoip);
            $data['ip_address'] = $ip_address;
            $data['created'] = new MongoDate();
	      
	    $this->save($data);                  
         //   $m = new Mongo("mongodb://gen.fordela.com:27017");
	 //   $collection = $m->vms->sess;
	 //   $collection->insert($data);
        }
        
        function init($data = array()){
        	/*
        	$ip_address = $_SERVER['REMOTE_ADDR']; //TODO use function that will work with load balancer
        	
        	//Replace Fordela or Local network with outside Fordela IP
        	if(substr($ip_address,0,5)=='10.1.' || $ip_address=='127.0.0.1'){
        		$ip_address = '173.164.249.253';
        	}
        	*/
        	
        	$data['_id'] = session_id();
        	$data['user_id'] = (int) Set::extract('Auth.User.id',$_SESSION);
        	$data['membership_id'] = (int) Set::extract('Auth.Membership.id',$_SESSION);
        	$data['created'] = new MongoDate();
        	//get GeoIp Record
        	//$data$record = $this->_geo_ip($ip_address);
        	//$geoip = (array) $record;
        	//$this->log($data,'sess_geoip');
        	unset($data['country_code3']);
        	unset($data['postal_code']);
        	unset($data['area_code']);
        	unset($data['dma_code']);
        	unset($data['metro_code']);
        	unset($data['continent_code']);
        	//$data = array_merge($data,$geoip);
        	return $this->save($data);
        }
        
        /**
         * TODO Finish this up
         * Updates a Mongo DB Sess Record
         *
         **/
        function update(){
        	$data = array('_id'=>session_id());
        	$data['user_id'] = (int) Set::extract('Auth.User.id',$_SESSION);
        	$data['membership_id'] = (int) Set::extract('Auth.Membership.id',$_SESSION);
        	return $this->save($data);
        }
        
        
        /**
         * Gets the Geo IP data from the IP address
         *
         **/
	function _geo_ip($ip_address){
		if(function_exists('geoip_record_by_name')){
			$geoiprecord = geoip_record_by_name($ip_address);
		}
		else{
			$geoiprecord = array(
				'country_code' => null,
				'country_name' => null,
				'region' => null,
				'city' => null,
				'latitude' => null,
				'longitude' => null,
			);
		}
		
		return $geoiprecord;
		/*
		if(!function_exists('geoip_country_code_by_addr')){			
			App::import('Vendor', 'GeoIp', array('file' => 'geoip/geoipcity.inc'));
			$dataLocation = TMP.'geoip'.DS.'GeoLiteCity.dat';
			$gi = geoip_open($dataLocation,GEOIP_STANDARD);
			$geoiprecord = geoip_record_by_addr($gi,$ip_address);
			return $geoiprecord;
		}
		else{
			
			return $geoiprecord;
		}
		*/	
	}

}