<?php
/**
 * 
 * Enter description here ...
 *
 */
class Mediainfo{

	/**
	 * 
	 * Enter description here ...
	 * @var unknown_type
	 */
	var $binPath = 'mediainfo';
	
	/**
	 * 
	 * Enter description here ...
	 * @var unknown_type
	 */
	var $mediaXml;
	/**
	 * 
	 * Enter description here ...
	 * @var unknown_type
	 */
	var $mediaPath = null;
	
	var $mediaParams = array(
		'filesize'=>0,
		'runtime'=>null,
		'fps'=>0,
		'width'=>0,
		'height'=>0,
		'video_codec'=>null,
		'video_bitrate'=>null
	);
	
	
	
	/**
	 * 
	 * Enter description here ...
	 * @param unknown_type $path
	 */
	function __construct($path=null){
		if(file_exists($path)){
			$this->mediaPath = $path;
			$this->mediaXml = $this->_invoke();	
			$results = $this->_getInfo();
			//CakeLog::write('MediaInfo/load',print_r($results,true));
			
			return $results;
			//$this->log($results, 'MediaInfo/load');
		}
	}
	
	
	
	/**
	 * 
	 * Enter description here ...
	 * @param unknown_type $path
	 */
	function analyze($path){
		
		if(file_exists($path)){
			$this->mediaPath = $path;
			$results = $this->_getInfo();
			
			//$this->log($results, 'MediaInfo/test');
			return $results;
			
		}else{
		
			return $path.' does not exist';
		}
	}
	
	function analyzeAudio($path){
		if(file_exists($path)){
			$this->mediaPath = $path;
			$this->mediaXml = $this->_invoke();
			$results = $this->_getAudioInfo();
				

			return $results;
				
		}else{
		
			return $path.' does not exist';
		}
		
		
		
		//$input = "mediainfo --Output=XML ".$path;
		//$output = array('');
		//exec($input.' 2>&1', $output);
		//return implode("\n", $output);
	}
	
	/**
	 * 
	 * Enter description here ...
	 */
	function _invoke() {
	    $input = "mediainfo --Output=XML ".$this->mediaPath;
		$output = array('');
		CakeLog::write('mediainfo',$input);
	    exec($input.' 2>&1', $output);

	    return trim(implode("\n", $output));
		
	}
	
	
	/**
	 * 
	 * Enter description here ...
	 */
	function _getInfo(){
		
		$parsedXml = $this->_readXML();
		CakeLog::write('mediainfo',print_r($parsedXml,true));
		if(!empty($parsedXml['Mediainfo']['File']['track'][0]['Complete_name'])){
			$this->mediaParams['filename']= $parsedXml['Mediainfo']['File']['track'][0]['Complete_name'];
			//Duration can be an array so check and get the first value 
			$this->mediaParams['runtime'] = (is_array($parsedXml['Mediainfo']['File']['track'][0]['Duration']))?$parsedXml['Mediainfo']['File']['track'][0]['Duration'][0]:$parsedXml['Mediainfo']['File']['track'][0]['Duration'];
			$this->mediaParams['fps'] = floatval($parsedXml['Mediainfo']['File']['track'][1]['Frame_rate']);
			$this->mediaParams['height']= $parsedXml['Mediainfo']['File']['track'][1]['Height'];
			$this->mediaParams['width']	= $parsedXml['Mediainfo']['File']['track'][1]['Width'];
			$this->mediaParams['video_codec'] = $parsedXml['Mediainfo']['File']['track'][1]['Codec_ID'];
			$this->mediaParams['video_bitrate'] = $parsedXml['Mediainfo']['File']['track'][1]['Bit_rate'];
			$this->mediaParams['audio_bitrate'] = $parsedXml['Mediainfo']['File']['track'][2]['Bit_rate'];
			$this->mediaParams['format'] = $parsedXml['Mediainfo']['File']['track'][1]['Format'];
		} else {
			$this->mediaParams = array();
		}
		return $this->mediaParams;
	}
	
	
	/**
	*
	* Enter description here ...
	*/
	function _getAudioInfo(){
	
		$parsedXml = $this->_readXML();
		CakeLog::write('mediainfo',print_r($parsedXml,true));
		$this->mediaParams['filename']= $parsedXml['Mediainfo']['File']['Track'][0]['Complete_name'];
		//Duration can be an array so check and get the first value
		$this->mediaParams['runtime'] = (is_array($parsedXml['Mediainfo']['File']['Track'][0]['Duration']))?$parsedXml['Mediainfo']['File']['Track'][0]['Duration'][0]:$parsedXml['Mediainfo']['File']['Track'][0]['Duration'];
			
		$this->mediaParams['audio_bitrate'] = $parsedXml['Mediainfo']['File']['Track'][1]['Bit_rate'];
			
		return $this->mediaParams;
	}
	
	/**
	 * 
	 * Enter description here ...
	 * Called by _getInfo();, Could probably be combined with that function
	 * 
	 * TODO: use XPATH instead of converting to array
	 */
	function _readXML() { 
	    //App::import('Xml'); 
		$data = explode("\r\n", $this->mediaXml);
		//CakeLog::write('mediainfo',$data[0]);
		
	    //$parsed_xml =& new XML($data[0]); 
	    $parsed_xml = Xml::toArray(new SimpleXMLElement($data[0])); 
		//$this->log($parsed_xml, 'ParsedXML');
	    return $parsed_xml; 
	  }
	  
}