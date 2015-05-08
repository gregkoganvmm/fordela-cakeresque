<?php
/**
 * Short description for video_version.php
 *
 * Long description for video_version.php
 *
 */
/**
 * VideoVersion class
 *
 * @uses          AppModel
 * @package       
 * @subpackage    
 */
class VideoVersion extends AppModel {
/**
 * name property
 *
 * @var string 'VideoVersion'
 * @access public
 */
	var $name = 'VideoVersion';
/**
 * displayField property
 *
 * @var string 'description'
 * @access public
 */
	var $displayField = 'description';
	
/**
 * belongsTo property
 *
 * @var array
 * @access public
 */
  
	var $belongsTo = array(
		'Video' =>
			array('className'  => 'Video',
				'conditions' => '',
				'order'      => '',
				'foreignKey' => 'video_id',
				'counterCache'=>true
    		),
  	);

/**
 * virtualFields property
 *
 * @var array
 * @access public
 */
  	
  	var $virtualFields = array(
    	'url' => 'CONCAT(host,dir,filename)',
	);
  	
  	
  	/**
  	 * gets object information from s3
  	 * 
  	 * @param $id
  	 * 
  	 * returns Array time,hash,type,size
  	 */
  	//TODO replace with new sdk
  	function getVersionS3Info($id, $bucket = VMS_MEDIA){
  		$this->id = $id;
  		$version = $this->read();
  
  		App::import('Vendor', 'S3', array('file' => 'aws-sdk/sdk.class.php'));
  
		$s3 = new AmazonS3();
		$s3->use_ssl = false;
		if($s3->if_object_exists($bucket, $version['VideoVersion']['dir'].$version['VideoVersion']['filename'])){
			return $s3->get_object_filesize($bucket, $version['VideoVersion']['dir'].$version['VideoVersion']['filename']);
		}
		else{
			return false;
		}
  	}
  	 	
  	
  	/**
  	* 
  	* Enter description here ...
  	* @param unknown_type $video_id
  	* @param unknown_type $version
  	*/
  	function getSourceUrl($video_version_id = null)
  	{
  		if($video_version_id){
  			$this->id = $video_version_id;
  		}
  		//$video_version = $this->read('location,archive,archive_dir,source_url',$this->id);
  		$video_version = $this->read('host,dir,filename',$this->id);
  		$user = rawurlencode(AWS_ID);
  		$pass = rawurlencode(AWS_KEY);
  		$source = null;
  		if(!empty($video_version['VideoVersion']['host'])){
  			$url = parse_url($video_version['VideoVersion']['host']);
  			extract($url);
  			if($scheme == 'sftp' || $scheme=='ftp'){
  				$scheme = 'ftp';
  				$user = rawurlencode(UPLOAD_USER);
  				$pass = rawurlencode(UPLOAD_PASS);
  			}elseif($scheme == 'http' && ($host=='archive.fordela.com' || $host=='archive-dev.fordela.com')){
  				$scheme = 's3';
  				$user = rawurlencode(AWS_ID);
  				$pass = rawurlencode(AWS_KEY);
  				//$host = $host.'.s3.amazonaws.com';
  		  	}elseif($scheme == 's3'){
  				$user = rawurlencode(AWS_ID);
  				$pass = rawurlencode(AWS_KEY);
  				//$host = $host.'.s3.amazonaws.com';
  			}
  			//rawurlencode the filename
  			$path = $video_version['VideoVersion']['dir'].$video_version['VideoVersion']['filename'];
  			//rebuild the source from its parts
  			$source = $scheme.'://'.$user.':'.$pass.'@'.$host.'/'.$path;
  		}
  		//$this->Encoding->setSource($source);
  		return $source;
  	
  	}
  	
  	
  	/**
  	 * 
  	 * @param $video_id
  	 */
	function defaultRecord($video_id,$filename){
		
		switch($filename){
			case 'Fordela_Demo.mp4':	
				$video_version['VideoVersion']['video_id']=$video_id;
				$video_version['VideoVersion']['encoding_profile'] = 'Fordela Demo';
				$video_version['VideoVersion']['encoding_profile_id'] = 0;
				$video_version['VideoVersion']['name']='Fordela Demo';
				$video_version['VideoVersion']['ext']='mp4';
				$video_version['VideoVersion']['filesize'] = 9751756;
				$video_version['VideoVersion']['width']= 400;
				$video_version['VideoVersion']['height']= 225;
				$video_version['VideoVersion']['status']='Finished';
				$video_version['VideoVersion']['dir']='demo/';
				$video_version['VideoVersion']['host']='http://'.VMS_MEDIA.'/';
				$video_version['VideoVersion']['format']='mp4';
				$video_version['VideoVersion']['fps']='23.98';
				$video_version['VideoVersion']['bitrate']=1500;
				$video_version['VideoVersion']['filename']='Fordela_Demo.mp4';
			break;
			
			case 'Nvidia_Testimonial.mp4':	
				$video_version['VideoVersion']['video_id']=$video_id;
				$video_version['VideoVersion']['encoding_profile'] = 'Nvidia Testimonial';
				$video_version['VideoVersion']['encoding_profile_id'] = 0;
				$video_version['VideoVersion']['name']='Nvidia Testimonial';
				$video_version['VideoVersion']['ext']='mp4';
				$video_version['VideoVersion']['filesize'] = 37853593;
				$video_version['VideoVersion']['width']= 1280;
				$video_version['VideoVersion']['height']= 720;
				$video_version['VideoVersion']['status']='Finished';
				$video_version['VideoVersion']['dir']='demo/';
				$video_version['VideoVersion']['host']='http://'.VMS_MEDIA.'/';
				$video_version['VideoVersion']['format']='mp4';
				$video_version['VideoVersion']['fps']='23.98';
				$video_version['VideoVersion']['bitrate']=1500;
				$video_version['VideoVersion']['filename']='Nvidia_Testimonial.mp4';
			break;
		
			case 'Stellar_Testimonial.mp4':
				$video_version['VideoVersion']['video_id']=$video_id;
				$video_version['VideoVersion']['encoding_profile'] = 'Stellar Testimonial';
				$video_version['VideoVersion']['encoding_profile_id'] = 0;
				$video_version['VideoVersion']['name']='Stellar Testimonial';
				$video_version['VideoVersion']['ext']='mp4';
				$video_version['VideoVersion']['filesize'] = 34183577;
				$video_version['VideoVersion']['width']= 1280;
				$video_version['VideoVersion']['height']= 720;
				$video_version['VideoVersion']['status']='Finished';
				$video_version['VideoVersion']['dir']='demo/';
				$video_version['VideoVersion']['host']='http://'.VMS_MEDIA.'/';
				$video_version['VideoVersion']['format']='mp4';
				$video_version['VideoVersion']['fps']='23.98';
				$video_version['VideoVersion']['bitrate']=1500;
				$video_version['VideoVersion']['filename']='Stellar_Testimonial.mp4';
			break;
		
			case 'Powerspeaking_Testimonial.mp4':
				$video_version['VideoVersion']['video_id']=$video_id;
				$video_version['VideoVersion']['encoding_profile'] = 'Powerspeaking Testimonial';
				$video_version['VideoVersion']['encoding_profile_id'] = 0;
				$video_version['VideoVersion']['name']='Powerspeaking Testimonial';
				$video_version['VideoVersion']['ext']='mp4';
				$video_version['VideoVersion']['filesize'] = 24012390;
				$video_version['VideoVersion']['width']= 1280;
				$video_version['VideoVersion']['height']= 720;
				$video_version['VideoVersion']['status']='Finished';
				$video_version['VideoVersion']['dir']='demo/';
				$video_version['VideoVersion']['host']='http://'.VMS_MEDIA.'/';
				$video_version['VideoVersion']['format']='mp4';
				$video_version['VideoVersion']['fps']='23.98';
				$video_version['VideoVersion']['bitrate']=1500;
				$video_version['VideoVersion']['filename']='Powerspeaking_Testimonial.mp4';
			break;
		}
		
		return $video_version;
	}
	
	/**
	 * 
	 */
  	function setup($video,$profile,$bitrate){
  		$this->create();
  		unset($profile['Profile']['id']);
  		$this->data['VideoVersion'] = $profile['Profile'];

  		$this->data['VideoVersion']['video_id']=$video['Video']['id'];
  		$this->data['VideoVersion']['format']=$profile['Profile']['output'];
  		$this->data['VideoVersion']['name']=$profile['Profile']['name'];
  		$this->data['VideoVersion']['dir']=$video['Video']['client_id'].'/';
  		$this->data['VideoVersion']['host']=VMS_ARCHIVE.'/';
  		
  		if($profile['Profile']['multi_bitrate']==1){
  			$this->data['VideoVersion']['bitrate']=$bitrate;
  		}

  		if($this->save($this->data))
  		{
  			$video_version_id = $this->id;
  			return $this->find('first', array(
  				'conditions'=> array('VideoVersion.id'=>$video_version_id),
  				'recursive'=>-1
  				)
  			);
  		} else {
  			return false;
  		}
  		
  	}
  	
  	/**
  	 * Create a version using the video source
  	 */
  	function createSourceVersion($video) {
  		$this->create();
  		$this->data['VideoVersion']['video_id'] = $video['Video']['id'];
  		$this->data['VideoVersion']['name'] = 'Source';
  		$this->data['VideoVersion']['filename'] = $video['Video']['archive'];
  		$this->data['VideoVersion']['ext'] = substr(strrchr($video['Video']['archive'], '.'), 1); // get file extension
  		$this->data['VideoVersion']['dir'] = $video['Video']['client_id'].'/';
  		$this->data['VideoVersion']['host']=VIDEO_UPLOAD_URL.'/';
  		$this->data['VideoVersion']['format'] = $this->data['VideoVersion']['ext'];
  		$this->data['VideoVersion']['filesize'] = $video['Video']['filesize'];
  		$this->data['VideoVersion']['width'] = $video['Video']['width'];
  		$this->data['VideoVersion']['height'] = $video['Video']['height'];
  		
  	  	if($this->save($this->data))
  		{
  			return $this->id;
  		} else {
  			return false;
  		}
  	}
  	
	/**
  	 * Create a version using the video source
  	 */
	function createVersion($video_id = null, $versionInfo = array()) {
				
		$this->Video->recursive = -1;
  		$video = $this->Video->read(null, $video_id);
				
		$version = $video['Video'];
		$version = array_merge($version,$versionInfo);
		
		if(empty($versionInfo['name'])){
			$version['name'] = 'Version_'.date('M-d-Y');
		}
		$ext = substr(strrchr( $version['filename'], '.'), 1);
						
  		$this->create();
  		$this->data['VideoVersion']['video_id'] = $video_id;
  		$this->data['VideoVersion']['name'] = $version['name'];
  		$this->data['VideoVersion']['filename'] = $version['archive'];
  		$this->data['VideoVersion']['ext'] = $ext; // get file extension
  		$this->data['VideoVersion']['dir'] = $version['dir'];
		$this->data['VideoVersion']['host'] = $version['host'];
  		$this->data['VideoVersion']['format'] = $ext;
  		$this->data['VideoVersion']['filesize'] = $version['filesize'];
  		$this->data['VideoVersion']['status'] = (!empty($version['status'])) ? $version['status'] : 'Received';
  		$this->data['VideoVersion']['fps'] = $version['fps'];
  		$this->data['VideoVersion']['width'] = $version['width'];
  		$this->data['VideoVersion']['height'] = $version['height'];
		$this->data['VideoVersion']['video_codec'] = $version['video_codec'];
		$this->data['VideoVersion']['audio_codec'] = $version['audio_codec'];
		$this->data['VideoVersion']['bitrate'] = $version['video_bitrate'];
		$this->data['VideoVersion']['audio_bitrate'] = $version['audio_bitrate'];
		$this->data['VideoVersion']['audio_sample_rate'] = $version['audio_sample_rate'];
		$this->data['VideoVersion']['runtime'] = $version['runtime'];
				
  	  	if($this->save($this->data))
  		{			
  			return $this->id;
  		} else {
  			return false;
  		}
  	}
  	
  	/**
  	 * Checks if the Source has a version record
  	 */
  	function isSourceVersion($video_id){
  		$version = $this->findByVideo_idAndName($video_id, 'Source Version', 'id');
  		if($version){
  			return true;
  		}else{
  			return false;
  		}
  	}
  	
  	/**
  	 * Create a version using the video source
  	 */
  	function create3DSourceVersion($video,$side,$filename){
  		$sourcePath = TMP.'uploads'.DS.$video['Video']['client_id'].DS.'videos'.DS.$filename;
  		$info = $this->Video->mediaInfo($sourcePath);
  		$this->create();
  		$this->data['VideoVersion']['video_id'] = $video['Video']['id'];
  		$this->data['VideoVersion']['name'] = $side.' Source';
  		$this->data['VideoVersion']['filename'] = $filename;
  		$this->data['VideoVersion']['ext'] = substr(strrchr($filename, '.'), 1); // get file extension
  		$this->data['VideoVersion']['dir'] = $video['Video']['client_id'].'/';
  		$this->data['VideoVersion']['host']=VIDEO_UPLOAD_URL.'/';
  		$this->data['VideoVersion']['format'] = $this->data['VideoVersion']['ext'];
  		$this->data['VideoVersion']['filesize'] = $info['filesize'];
  		$this->data['VideoVersion']['width'] = $info['width'];
  		$this->data['VideoVersion']['height'] = $info['height'];
  		
  	  	if($this->save($this->data))
  		{
  			return $this->id;
  		}
  		else
  		{
  			return false;
  		}
  	}
  	
  	/* SEARCH PLUGIN CODE */
  	
  	public $filterArgs = array(
	  	array(
	  	    	'name' => 'video_id', 
	  	    	'type' => 'query',
	  	    	'method' => 'videoIdSearch'
	  	),
	  	array(
	  		   'name' => 'id_search', 
	  		   'type' => 'query',
	  		   'method' => 'idSearch'
	  	)
  	);
  	
  	
  	public function idSearch($data = array()) {
  		$filter = $data['id_search'];
  		$cond = array(
  			    'OR' => array(
  			       'VideoVersion.id' => $filter
  		));
  		return $cond;
  	}
  	
  	public function videoIdSearch($data = array()) {
  		$filter = $data['video_id'];
  		$cond = array(
  	            'OR' => array(
  	                'VideoVersion.video_id' => $filter,
  		));
  		return $cond;
  	}  	
  	/* END SEARCH PLUGIN CODE */
  	
}
?>
