<?php
/**
 * Short description for client.php
 *
 * Long description for client.php
 *
 *$Id: client.php 13679 2012-03-26 17:53:31Z joel $
 *
 * @package       vms
 * @subpackage    vms.app.models
 * @since         v 1.0
 */
/**
 * Client class
 *
 * @uses          AppModel
 * @package       vms
 * @subpackage    vms.app.models
 */
class Client extends AppModel {

	/**
	 * Client
	 *
	 * @var string $name Name
	 */
	var $name = 'Client';

	var $actsAs = array('Acl' => array('type'=> 'requester'),'Containable');

	var $validate = array(
			'company' => array(
				'notempty' => array(
					'rule' => array('notempty'),
				),
			),
			'email' => array(
				'email' => array(
					'rule' => array('email'),
				),
			),
			//'phone' => array(
			//	'phone' => array(
			//		'rule' => array('phone'),
			//	),
			//),
			'active' => array(
				'boolean' => array(
					'rule' => array('boolean'),
				),
			),
			'paid' => array(
				'boolean' => array(
					'rule' => array('boolean'),
				),
			),
			'timezone' => array(
				'notempty' => array(
					'rule' => array('notempty'),
				),
			),
			'date_format' => array(
				'notempty' => array(
					'rule' => array('notempty'),
				),
			),
			//'default_profile' => array(
			//	'notempty' => array(
			//		'rule' => array('notempty'),
			//	),
			//),
			'primary_profile' => array(
				'notempty' => array(
					'rule' => array('notempty'),
				),
			),
			'video_count' => array(
				'numeric' => array(
					'rule' => array('numeric'),
				),
			),
			'user_count' => array(
				'numeric' => array(
					'rule' => array('numeric'),
				),
			),
			'playlist_count' => array(
				'numeric' => array(
					'rule' => array('numeric'),
				),
			),
			'type' => array(
				'numeric' => array(
					'rule' => array('numeric'),
				),
			),
			'subdomain' => array(
				'notempty' => array(
					'rule' => array('notempty'),
				),
				'alphanumeric' => array(
					'rule' => array('alphanumeric'),
				),
				'minlength' => array(
					'rule' => array('minlength',3),
				),
			),
			'archive_size' => array(
				'numeric' => array(
					'rule' => array('numeric'),
				),
			),
			'archive_bucket_size' => array(
				'numeric' => array(
					'rule' => array('numeric'),
				),
			),
			'archive_bandwidth' => array(
				'numeric' => array(
					'rule' => array('numeric'),
				),
			),
			'archive_bucket_count' => array(
				'numeric' => array(
					'rule' => array('numeric'),
				),
			),
			'media_count' => array(
				'numeric' => array(
					'rule' => array('numeric'),
				),
			),
			'media_size' => array(
				'numeric' => array(
					'rule' => array('numeric'),
				),
			),
			'media_bucket_size' => array(
				'numeric' => array(
					'rule' => array('numeric'),
				),
			),
			'media_bucket_count' => array(
				'numeric' => array(
					'rule' => array('numeric'),
				),
			),
			'all_files_size' => array(
				'numeric' => array(
					'rule' => array('numeric'),
				),
			),
			'all_buckets_size' => array(
				'numeric' => array(
					'rule' => array('numeric'),
				),
			),
			'max_storage' => array(
				'numeric' => array(
					'rule' => array('numeric'),
				),
			),
			'streaming_bandwidth' => array(
				'numeric' => array(
					'rule' => array('numeric'),
				),
			),
			'progressive_bandwidth' => array(
				'numeric' => array(
					'rule' => array('numeric'),
				),
			),
			'total_bandwidth' => array(
				'numeric' => array(
					'rule' => array('numeric'),
				),
			),
			'max_monthly_bandwidth' => array(
				'numeric' => array(
					'rule' => array('numeric'),
				),
			),
	);

	/**
	 * HasAndBelongsToMany
	 * @var array
	 */
	var $hasAndBelongsToMany = array(
		'User'=>array('with'=>'Membership')
	);

	/**
	 * BelongsTo Association
	 *
	 * @var array $belongsTo Association
	 */
	var $belongsTo = array(
		'ClientPlan' =>
  		array('className'  => 'ClientPlan',
    	'conditions' => '',
    	'order'      => '',
    	'foreignKey' => 'client_plan_id'
  		),
		'ClientType' =>
  		array('className'  => 'ClientType',
    	'conditions' => '',
    	'order'      => '',
    	'foreignKey' => 'client_type_id'
  		),
  		'PrimaryUser' =>
  		array('className'  => 'User',
    	'conditions' => '',
    	'order'      => '',
  		'fields'	=>	'id,username,first_name,last_name',
    	'foreignKey' => 'user_id'
  		)
	);

	/**
	 * HasMany Association
	 *
	 * @var array $hasMany Association
	 */
	var $hasMany = array(
		'Playlist' => array(
			'className'  => 'Playlist',
			'dependent'  =>  true,
			'foreignKey' => 'client_id',
			'limit'=>'100'

		),
		'Video' => array(
			'className'  => 'Video',
			'dependent'  =>  true,
			'foreignKey' => 'client_id',
			'fields'=> 'id,title,client_id',
			'conditions'=>array(
				'Video.deleted'=>0
			)
		),
		'UserType'=>array(
			'className'  => 'UserType',
			'dependent'  =>  true,
			'foreignKey' => 'client_id'
		)
	);

	/**
	 * HasOne Association
	 *
	 * @var array $hasOne Association
	 */
	var $hasOne = array('Subscription' =>
  		array('className'  => 'Subscription',
    		'dependent'  =>  false,
			'foreignKey' => 'client_id'
		),
	);

	var $defaultData = array();


        function makeUploadFolders($client_id){
            App::import('Lib','Ftp');
            $this->ftp = new Ftp(UPLOAD_HOST,UPLOAD_USER,UPLOAD_PASS);

            $this->ftp->connect();

            $this->ftp->make_dir($client_id);
            $this->ftp->make_dir($client_id.'/videos');
            $this->ftp->make_dir($client_id.'/audio');
            $this->ftp->make_dir($client_id.'/images');

            $this->ftp->close();
        }

		//Updates the User Aro after the membership record is created adds parent_id and alias
	function addPrimaryUserAro($user_id,$client_id)
	{
		App::import('Model', 'DbAcl');
		$this->Aro = new Aro();

        $membership = $this->User->Membership->find('first',array('conditions'=>array('Membership.user_id'=>$user_id,'Membership.client_id'=>$client_id)));
		$Aro = $this->Aro->find('first', array('conditions' => array('model' => 'Membership', 'foreign_key' => $membership['Membership']['id'])));
		$parentAro = $this->Aro->find('first', array('conditions' => array('model' => 'UserType', 'foreign_key' => $membership['Membership']['user_type_id'])));

		$this->Aro->id = $Aro['Aro']['id'];
		$item['alias'] = $membership['User']['username'];
		$item['parent_id'] = $parentAro['Aro']['id'];
		//Save data
		$this->Aro->save($item);
	}

	function addUserTypesAros($client_id)
	{
		App::import('Model', 'DbAcl');
		$this->Aro = new Aro();

		$this->User->Membership->UserType->bindModel(array('belongsTo'=>array('Client')));
		$conditions['UserType.client_id'] = $client_id;
		$fields = 'UserType.id,UserType.client_id,UserType.name,Client.name';
		$userTypes = $this->User->Membership->UserType->find('all',compact('fields','conditions'));
		$clients = array();
		$parentAro = $this->Aro->find('first',
				array('conditions' =>
					array(
						'model' => 'Client',
						'foreign_key' => $client_id
					)
				)
			);

		//Iterate and create AROs (as children)
		foreach($userTypes as $userType)
		{
			//$this->out($userType['UserType']['name']);
			$item = array();
			//$item['parent_id'] = 0;
			$item['model'] = 'UserType';
			$item['foreign_key'] = $userType['UserType']['id'];
			//$item['alias'] = $userType['UserType']['name'].' - '.$userType['Client']['name'];
			$item['alias'] = $userType['UserType']['name'].' - '.$userType['Client']['name'];
			$item['parent_id'] = $parentAro['Aro']['id'];
			$this->Aro->create();
			$this->Aro->save($item);
		}

	}




	function updateLatestActivity($client_id){

		$models = array(
			//'User',
			'Video',
			'Playlist'
		);
		foreach($models as $model){
			$latest = 0;
			$order = $model.'.modified DESC';
			$conditions = array($model.'.client_id'=>$client_id);
			$recursive = -1;
			$fields = array('id','modified');
			$item = $this->$model->find('first',compact('order','recursive','fields','conditions'));
			$latestActivity = strtotime($item[$model]['modified']);
			$this->log($latestActivity,'client_activity');
			if($latestActivity>$latest){
				$latest = $latestActivity;
			}
		}
		$this->id = $client_id;
		$this->saveField('latest_activity',date('Y-m-d H:i:s',$latest));
	}

	/**
	 *
	 * @param int $client_id
	 */
	function generateApiKeys($client_id){
		$api_id = '';
		$api_key = '';
		$api_id_length = 32;
		$api_key_length = 64;

		$chars = array_merge(range('A','Z'),range(0,9));
		$charsLength = count($chars);

		for ( $counter = 0; $counter < $api_id_length; $counter++) {
			$key = rand(0,$charsLength-1);
			$api_id .= $chars[$key];
		}

		for ( $counter = 0; $counter < $api_key_length; $counter++) {
			$key = rand(0,$charsLength-1);
			$api_key .= $chars[$key];
		}

		$this->id = $client_id;
		$this->saveField('api_id',$api_id);
		$this->saveField('api_key',$api_key);
		return true;
	}

	function monthlyBandwidth($client_id,$month=null){
		$return = array(
			'streaming_bandwidth'=>0,
			'progressive_bandwidth'=>0,
			'archive_bandwidth'=>0,
			'total_bandwidth'=>0
		);
		if($month==null){
			$month = date('n');
		}

		//Streaming Bandwidth
		$sql = "Select sum(value) as streaming_bandwidth from usage_stats where type = 3 and client_id = $client_id and month(date) = $month group by year(date),month(date)";
		$results = $this->query($sql);
		if(!empty($results[0][0]['streaming_bandwidth'])){
			$return['streaming_bandwidth'] = $results[0][0]['streaming_bandwidth'];
		}

		//Progressive Bandwidth
		$sql = "Select sum(value)  as progressive_bandwidth from usage_stats where type = 2 and client_id = $client_id and month(date) = $month group by year(date),month(date)";
		$progResults = $this->query($sql);
		if(!empty($progResults[0][0]['progressive_bandwidth'])){
			$return['progressive_bandwidth'] = $progResults[0][0]['progressive_bandwidth'];
		}

		//S3 Archive bandwidth
		$sql = "Select sum(value)  as archive_bandwidth from usage_stats where type = 4 and client_id = $client_id and month(date) = $month group by year(date),month(date)";
		$archResults = $this->query($sql);
		if(!empty($archResults[0][0]['archive_bandwidth'])){
			$return['archive_bandwidth'] = $archResults[0][0]['archive_bandwidth'];
		}

		//$return['total_bandwith'] = $return['progressive_bandwith']+$return['streaming_bandwith'];
		//Add Progressive and Streaming to get Total
		//$return['total_bandwidth'] = (($return['progressive_bandwidth']+($return['streaming_bandwidth']*100)+ $return['archive_bandwidth']));
		$return['total_bandwidth'] = ($return['progressive_bandwidth']+$return['streaming_bandwidth'] + $return['archive_bandwidth']);
		$this->log($client_id,'monthlyBandwidth');
		$this->log($return,'monthlyBandwidth');
		return $return;

	}

	function librarySize($client_id){
		$sql = "select sum(runtime) as runtime from videos where client_id = $client_id and active = 1 and deleted = 0";
		$results = $this->query($sql);
		$time = date("H:i:s",$results[0][0]['runtime']);
		return $time;
		//debug($time);
	}

//	function parentNode(){
//        if (!$this->id) {
//            return null;
//        }
//        $data = $this->read();
//        if (!$data['Client']['client_type_id']){
//            return null;
//        } else {
//            return array('model' => 'ClientType', 'foreign_key' => $data['Client']['client_type_id']);
//        }
//    }

    public function parentNode() {
		if (!$this->id && empty($this->data)) {
		    return null;
		}
		if (isset($this->data['Client']['client_type_id'])) {
		    $clientTypeId = $this->data['Client']['client_type_id'];
		} else {
		    $clientTypeId = $this->field('client_type_id');
		}
		if (!$clientTypeId) {
		    return null;
		} else {
		    return array('ClientType' => array('id' => $clientTypeId));
		}
	    }
/* Removed Code Because it was used to keep VV seperate but shouldn't be needed anymore since they are on a seperate bucket
    function beforeSave(){
    	if(empty($this->data['Client']['id']))
    	{
    		$this->data['Client']['id'] = $this->getNextInsertId();
    	}
    	return true;
    }
*/
	/*

    function beforeDelete()
    {
    	$client_id = $this->id;
    	return $this->removeContentFromSystem($client_id);
	}


	function removeContentFromSystem($client_id){
		//TODO delete s3 assets.
		$s3 = new AmazonS3();


		$bucket = VMS_ARCHIVE;
		$folder = $client_id.'/';

		CFArray::init($s3->get_object_list($bucket, array(
		    'prefix' => $folder
		)))->each(function($node, $i, $s3) {
			$s3->batch()->delete_object($bucket, $node);
		}, array($s3));
		$responses = $s3->batch()->send();
		var_dump($responses->areOK());


		$bucket = VMS_MEDIA;

		CFArray::init($s3->get_object_list($bucket, array(
		    'prefix' => $folder
		)))->each(function($node, $i, $s3) {
			$s3->batch()->delete_object($bucket, $node);
		}, array($s3));
		$responses = $s3->batch()->send();
		var_dump($responses->areOK());


		return true;
	}
	*/

    /**
     *
     * @param $client_id
     */
    function setDefaultEncodingProfile($client_id)
    {
    	$q = "
    		UPDATE
    			clients C,
    			encoding_profiles EP
    		SET
    			C.primary_profile = EP.id,
    			C.default_profile = EP.id
    		WHERE
    			C.id = $client_id
    		AND
    			EP.name = 'Proxy File'
    		AND
    			EP.client_id = C.id;
    		";

    	return $this->query($q);
    }

    /**
     * Surveys all systems to find out the next id to insert
     */
    function getNextInsertId(){
    	$nextId = 0;
    	$resellers = VMS::$resellers;
    	$resellers[] = 'main';
   		foreach($resellers as $r){
   			$this->useDbConfig = $r;
   			$lastId = $this->field('id', array(), 'id DESC');
   			if($lastId > $nextId){
				$nextId = $lastId;
   			}
    	}
    	//Reset the DB config
    	$this->useDbConfig = 'default';
    	//Add one more
    	$nextId++;
    	return $nextId;
    }


    function __findReport(){
    	App::import('Helper','Number');
    	$number = new NumberHelper(new View(null));
		$conditions = array();
		//$recursive = -1;
		$fields = array(
			'Client.id',
			'Client.name',
			'subdomain',
			'all_buckets_size',
			'video_count',
			'playlist_count',
			'user_count',
			'max_monthly_bandwidth',
			'max_storage',
			'client_plan_id',
			'ClientPlan.name',

		);

		$contain = array('ClientPlan');
		$order = 'Client.id ASC';
		$clients=$this->find("all",compact('conditions','fields','order','contain'));
		//$this->log($clients,"TestMylog");
		foreach($clients as $k=>$client)
		{


			$clients[$k]['Client'] = array_merge($clients[$k]['Client'],$this->monthlyBandwidth($clients[$k]['Client']['id']));
			$clients[$k]['Client']['monthly_bandwidth'] = ($clients[$k]['Client']['progressive_bandwidth']+($clients[$k]['Client']['streaming_bandwidth'])+ ($clients[$k]['Client']['archive_bandwidth']));
			$clients[$k]['Client']['monthly_bandwidth_readable'] = $number->toReadableSize($clients[$k]['Client']['monthly_bandwidth']);
			$clients[$k]['Client']['max_monthly_bandwidth'] = $clients[$k]['Client']['max_monthly_bandwidth'];
			$clients[$k]['Client']['max_monthly_bandwidth_readable'] = $number->toReadableSize($clients[$k]['Client']['max_monthly_bandwidth']);
			$clients[$k]['Client']['bandwidth_overage'] = ((($clients[$k]['Client']['max_monthly_bandwidth']) - ($clients[$k]['Client']['monthly_bandwidth'])) > 0 ) ? 0 : (($clients[$k]['Client']['monthly_bandwidth'])-($clients[$k]['Client']['max_monthly_bandwidth']));
			$clients[$k]['Client']['bandwidth_overage_readable'] =   $number->toReadableSize($clients[$k]['Client']['bandwidth_overage']);
			$clients[$k]['Client']['total_storage'] = $clients[$k]['Client']['all_buckets_size'];
			$clients[$k]['Client']['total_storage_readable'] = $number->toReadableSize($clients[$k]['Client']['total_storage']);
			$clients[$k]['Client']['max_storage'] = $clients[$k]['Client']['max_storage'];
			$clients[$k]['Client']['max_storage_readable'] = $number->toReadableSize($clients[$k]['Client']['max_storage']);
			$clients[$k]['Client']['storage_overage'] = (($clients[$k]['Client']['max_storage']-$clients[$k]['Client']['total_storage']) > 0 )? 0 : (($clients[$k]['Client']['total_storage'])-($clients[$k]['Client']['max_storage']));
			$clients[$k]['Client']['storage_overage_readable'] = $number->toReadableSize($clients[$k]['Client']['storage_overage']);
			$clients[$k]['Client']['archive_bandwidth'] = $number->toReadableSize($clients[$k]['Client']['archive_bandwidth']);
			$clients[$k]['Client']['client_plan'] = $clients[$k]['ClientPlan']['name'];
			$clients[$k]['Client']['user_count'] = $clients[$k]['Client']['user_count'];

		}


		return $clients;
    }

	/**
	 * Moves an image from one folder into another
	 * Currently moves to the maped img server
	 * @param $client_id
	 */
	function uploadLogo($client_id){
		if ($_FILES['client_logo']['error'] == "2")
		{ // file size error
			$uploadmsg = "Error, file ".$_FILES['client_logo']['name']." is too large, please try again. A notification has been sent to Fordela. If you would like immediate technical support, please contact support@fordela.com or click on the Live Help link on the left.'";
			//$this->_error_report($uploadmsg, $_SERVER['REQUEST_URI']);
		}
		else if ($_FILES['client_logo']['error'] == "4")
		{ // file name error
			$uploadmsg = "Error, filename ".$_FILES['client_logo']['name']." was invalid. A notification has been sent to Fordela. If you would like immediate technical support, please contact support@fordela.com or click on the Live Help link on the left.'";
			//$this->_error_report($uploadmsg, $_SERVER['REQUEST_URI']);
      	}
		else if (@is_uploaded_file($_FILES["client_logo"]["tmp_name"]))
      	{ //if file uploaded ok
			$extension = strtolower(substr($_FILES['client_logo']['name'], -4)); //set extension
    		if ($extension == ".jpg" OR $extension == ".gif" OR $extension == ".png")
    		{ //if extension is valid
				//set new file name to clientid_videoid.jpg
				$newfilename = $client_id.'_'.strtotime(date('Y-m-d H:i:s')).$extension;
				$newFilePath = 'clients/'.$newfilename;
				$fileSrc=$_FILES["client_logo"]["tmp_name"];
				//copy the file to the screenshot directory
				$newFilePath = IMG_UPLOAD_ROOT.$newFilePath;
				if (copy($fileSrc,$newFilePath))
				{
					$this->id = $client_id;
					$this->saveField('custom_logo',$newfilename);
					return true;
				}
				else
				{
					return false;
				}
    		}
      	}
	}

	function updateSubDomain($client_id,$old_subdomain,$new_subdomain)
	{
		$d = ClassRegistry::init('Domain');
		$domain = $d->find('first',array('conditions'=>array('client_id'=>$client_id,'subdomain'=>$old_subdomain,'sld'=>'fordela')));

		if($domain){
			$domain['Domain']['domain'] = $new_subdomain.'.fordela.com';
			$domain['Domain']['subdomain'] = $new_subdomain;
			return $d->save($domain);
		}
		return false;
	}

    protected function _copyToDisabled($bucket,$objects) {
        if(count($objects) < 1) {
            return;
        }
        foreach($objects as $object) {
            if($object['Size'] > 0) {
                $this->Aws->get('S3','copyObject',array(
                    'Bucket' => $bucket,
                    'CopySource' => $bucket.'/'.$object['Key'],
                    'Key' => 'DISABLED/'.$object['Key']
                ));
            }
        }
        return;
    }

    protected function _copyToClient($bucket,$objects) {
        foreach($objects as $object) {
            // Set object key without "DISABLED/" prefix
            $key = str_replace('DISABLED/','',$object['Key']);
            if($object['Size'] > 0) {
                $this->Aws->get('S3','copyObject',array(
                    'Bucket' => $bucket,
                    'CopySource' => $bucket.'/'.$object['Key'],
                    'Key' => $key
                ));
            }
        }
        return;
    }

    protected function _clearKeyPath($bucket,$objects) {
        if(count($objects) < 1) {
            return;
        }
        foreach($objects as $object) {
            if($object['Size'] > 0) {
                $this->Aws->get('S3','deleteObject',array(
                    'Bucket' => $bucket,
                    'Key' => $object['Key']
                ));
            }
        }
        return;
    }

    protected function _initAws() {
        //Init AwsComponent
        App::uses('ComponentCollection','Controller');
        App::uses('AwsComponent','Controller/Component');
        $Collection = new ComponentCollection();
        return $this->Aws = new AwsComponent($Collection);
    }

    /**
     * Copy / moves Archive and Media assets to DISABLED key path
     * TODO: Move this processing of this to JobQueue
     *
     * @param Int $client_id
     */
    function disable($client_id) {
        $this->id = $client_id;
        $prefix = $client_id.'/';
        //Disable VMS account
        if(!$this->saveField('active',0)) {
            return false;
        }
        $this->_initAws();
        // Archive
        $archiveList = $this->Aws->get('S3','ListObjects',array('Bucket' => VMS_ARCHIVE,'Prefix' => $prefix));
        $this->_copyToDisabled(VMS_ARCHIVE,$archiveList['Contents']);
        $this->_clearKeyPath(VMS_ARCHIVE,$archiveList['Contents']);
        // Media
        $mediaList = $this->Aws->get('S3','ListObjects',array('Bucket' => VMS_MEDIA,'Prefix' => $prefix));
        $this->_copyToDisabled(VMS_MEDIA,$mediaList['Contents']);
        $this->_clearKeyPath(VMS_MEDIA,$mediaList['Contents']);
    }

    /**
     *
     * enable client assets and account
     * @param Int $client_id
     */
    function enable($client_id){
        $this->id = $client_id;
        $prefix = 'DISABLED/'.$client_id.'/';
        // Mark client active
        if(!$this->saveField('active',1)){
            return false;
        }
        //If account has not been varified yet do so
        $tokens = ClassRegistry::init('Token');
        $token = $tokens->find('first',array('conditions'=>array('client_id'=>$client_id, 'retrieved'=>null)));
        if(isset($token['Token']['id']) && !empty($token['Token']['id'])){
            $tokens->id = $token['Token']['id'];
            $tokens->saveField('retrieved',NOW);
        }

        $this->_initAws();
        // Archive
        $archive = $this->Aws->get('S3','ListObjects',array('Bucket' => VMS_ARCHIVE,'Prefix' => $prefix));
        $this->_copyToClient(VMS_ARCHIVE,$archive['Contents']);
        $this->_clearKeyPath(VMS_ARCHIVE,$archive['Contents']);
        // Media
        $media = $this->Aws->get('S3','ListObjects',array('Bucket' => VMS_MEDIA,'Prefix' => $prefix));
        $this->_copyToClient(VMS_MEDIA,$media['Contents']);
        $this->_clearKeyPath(VMS_MEDIA,$media['Contents']);
    }


    /**
     *
     * Delete client assets and account
     * @param Int $client_id
     */
    function delete_assets($client_id){
        $this->_initAws();
        $prefix = $client_id.'/';
        // Archive
        $archive = $this->Aws->get('S3','ListObjects',array('Bucket' => VMS_ARCHIVE,'Prefix' => $prefix));
        $this->_clearKeyPath(VMS_ARCHIVE,$archive['Contents']);
        // Media
        $media = $this->Aws->get('S3','ListObjects',array('Bucket' => VMS_MEDIA,'Prefix' => $prefix));
        $this->_clearKeyPath(VMS_MEDIA,$media['Contents']);
        // Smooth Streaming
        $ss_media = $this->Aws->get('S3','ListObjects',array('Bucket' => VMS_SS_MEDIA,'Prefix' => $prefix));
        $this->_clearKeyPath(VMS_SS_MEDIA,$ss_media['Contents']);
        // Archive disabled
        $disabled_archive = $this->Aws->get('S3','ListObjects',array('Bucket' => VMS_ARCHIVE,'Prefix' => 'DISABLED/'.$prefix));
        $this->_clearKeyPath(VMS_ARCHIVE,$disabled_archive['Contents']);
        // Media disabled
        $disabled_media = $this->Aws->get('S3','ListObjects',array('Bucket' => VMS_ARCHIVE,'Prefix' => 'DISABLED/'.$prefix));
        $this->_clearKeyPath(VMS_ARCHIVE,$disabled_media['Contents']);
    }

	/* SEARCH PLUGIN CODE */

    public $filterArgs = array(
    	array(
    	'name' => 'name',
    	 'type' => 'query',
    	 'method' => 'nameSearch'
    	),
    	array(
    	'name' => 'id_search',
    	 'type' => 'query',
    	 'method' => 'idSearch'
    	)
    );


    public function nameSearch($data = array()) {
        $filter = $data['name'];
        $cond = array(
            'OR' => array(
                'Client.name LIKE' => '%' . $filter . '%',
                'Client.subdomain LIKE' => '%' . $filter . '%'
            ));
        return $cond;
    }

    public function idSearch($data = array()) {
        $filter = $data['id_search'];
        $cond = array(
            'OR' => array(
                'Client.id' => $filter
            ));
        return $cond;
    }

    /**
     *
     * Enter description here ...
     * @return Ambigous <boolean, unknown>
     */
    function getDefaultData(){
    	if(empty($this->defaultData)){
    		$configFile = VMS_DEFAULT_DATA;
    		$this->defaultData = parse_ini_file (  $configFile ,  $process_sections = true , $scanner_mode = INI_SCANNER_NORMAL  );
    		return (!empty($this->defaultData))?$this->defaultData:false;
    	}else{
    		return $this->defaultData;
    	}
    }
	/* END SEARCH PLUGIN CODE */

    function createDefaultDomain($client_id){
    	return $this->query("INSERT INTO
	domains

	(name,client_id,subdomain,https,sld,tld)
(SELECT

	CONCAT(subdomain,'.fordela.com') as name,
	id,
	subdomain ,
	'1',
	'fordela',
	'com'
FROM
	clients
WHERE id = $client_id)");
    }


    function validate_subdomain($subdomain, $client_id = null){
    	$this->log(func_get_args(),'validate_subdomain');
    	$conditions = array(
    		'Client.subdomain'=>$subdomain,
    		'Client.active'=>1
    	);

    	if(!empty($client_id)){
    		$conditions['Client.id <>'] = $client_id;
    	}

    	if($this->find('first',compact('conditions')))
    	{
    		return false;
    	}
    	else
    	{
    		return true;
    	}
   }

   /**
    * check to see what clients have not activated their account in the set amount of time and disable them.
    *
    **/
   function cleanup(){
        $tokens = ClassRegistry::init('Token');

        $date = Date('Y-m-d H:i:s', strtotime("-4 days"));

        //get all tokens that are more then 3 days old and have not been activated
        $conditions = array(
            'retrieved' =>null,
            'created <' => $date,
            'NOT'=>array(
                'client_id' => null
            )
        );

        $records = $tokens->find('all',array('conditions'=>$conditions));

        foreach($records as $record){
            $this->recursive = -1;

            //disable login and move assets
            $this->disable($record['Token']['client_id']);

        }

        //get all tokens that are more then 14 days old and have not been activated

        //$date = Date('Y-m-d H:i:s', strtotime("-14 days"));
        //
        //$conditions = array(
        //    'retrived' =>null,
        //    'created >' => $date,
        //    'NOT'=>array(
        //        'client_id' => null
        //    )
        //);
        //
        //$records->find('all',array('conditions'=>$conditions));
        //
        //foreach($records as $record){
        //    $this->recursive = -1;
        //
        //    //disable login and move assets
        //    $this->enable($record['Token']['client_id']);
        //    $this->delete($record['Token']['client_id']);
        //    $this->delete_assets($record['Token']['client_id']);
        //}

   }
}
?>
