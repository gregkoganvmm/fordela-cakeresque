<?php
/**
 * Shell for any extra HLS related tasks
 */
use Aws\Common\Aws;

class HlsShell extends Shell
{
    var $uses = array(
        'Client',
        'Domain',
        'JobQueue',
        'Video',
        'VideoVersion'
    );

    var $status = array();

    public function perform() {
        $this->initialize();
        $this->{array_shift($this->args)}();
    }

    public function copyHlsDomainManifest()
    {
        $client_id = $this->args[0];
        $video_id = $this->args[1];

        // Update the HLS version record size
        $this->_getHlsSize($client_id,$video_id);

        // Get client subdomain from client record
        $subdomain = $this->Client->field('subdomain',array('id' => $client_id));

        // Get custom url from client domain record
        $domain = $this->Domain->find('first',array(
            'conditions' => array('client_id' => $client_id),
            'fields' => array('subdomain','sld','tld')
        ));
        $domain_url = $domain['Domain']['subdomain'].'.'.$domain['Domain']['sld'].'.'.$domain['Domain']['tld'];
        $this->out($domain_url);

        $this->status['description'] = 'Manifests not copied - No custom domain'; // Default

        // If custom domain, copy manifests with custom domain licensing url
        if($domain_url <> $subdomain.'.fordela.com') {

            // Init AWS SDK and use iterator to get list of manifests
            $s3 = Aws::factory(AWS_OREGON)->get('S3');
            $s3->registerStreamWrapper();

            $iterator = $s3->getIterator('ListObjects', array('Bucket' => MEDIA_HLS,'Prefix' => $client_id.'/'.$video_id.'/'));
            $manifests = array();
            foreach ($iterator as $object) {
                if(substr($object['Key'], -4) === 'm3u8') {
                    $manifests[] = 's3://'.MEDIA_HLS.'/'.$object['Key'];
                }
            }

            // Loop through and create custom manifest for each found manifest
            foreach($manifests as $manifest) {
                $this->out('Copying '.$manifest);
                $info = pathinfo($manifest);

                $isMaster = (strpos($info['basename'],'playlist') !== false) ? true : false;
                $output = '';
                if ($stream = fopen($manifest, 'r')) {
                    while (!feof($stream)) {
                        $output .= fread($stream, 1024);
                    }
                    fclose($stream);
                }
                if(!$isMaster) {
                    // Bitrate version manifest
                    $output = str_replace('https://'.$subdomain.'.fordela.com','http://'.$domain_url,$output);
                    file_put_contents($info['dirname'].'/'.$info['filename'].'_'.$domain_url.'.m3u8', $output);
                    $this->out('done');
                } else {
                    // Master manifest
                    $output = str_replace('.m3u8','_'.$domain_url.'.m3u8',$output);
                    file_put_contents($info['dirname'].'/'.$info['filename'].'_'.$domain_url.'.m3u8', $output);
                }
            }
            $this->status['description'] = 'Manifests copied - Client has custom domain';
        }

        $this->status['status'] = 'Finished';
        $jobId = end(array_values($this->args));
        $this->JobQueue->updateJob($jobId,$this->status);
    }

    public function deleteHlsVideo()
    {
        if( empty($this->args[0]) || empty($this->args[1]) ) {
            $this->out('Error! ClientID and VideoID arguments are required');die;
        }
        $client_id = $this->args[0];
        $video_id = $this->args[1];

        $s3 = Aws::factory(AWS_OREGON)->get('S3');
        $hasObjects = true;

        $this->out("For ClientID {$client_id} deleting HLS segments for VideoID {$video_id}");
        $objCount = 0;

        while($hasObjects) {
            $list = $s3->listObjects(array(
                'Bucket' => MEDIA_HLS,
                'Prefix' => $client_id.'/'.$video_id.'/'
            ));

            if( count($list['Contents']) > 0) {
                $toDeleteArray = array();
                foreach($list['Contents'] as $k => $object) {
                    $objCount++;
                    // Setup array of objects to delete
                    $toDeleteArray[] = array('Key' => $object['Key']);
                }
                // Now delete those objects
                $s3->deleteObjects(array(
                    'Bucket' => MEDIA_HLS,
                    'Objects' => $toDeleteArray
                ));
            } else {
                $hasObjects = false;
            }
        }
        $this->out('Finished - Total objects removed: '.$objCount);
    }

    /**
     * Call this from command line to run manually
     */
    public function getHlsSize()
    {
        if( empty($this->args[0]) || empty($this->args[1]) ) {
            $this->out('Error! ClientID and VideoID arguments are required');die;
        }
        $client_id = $this->args[0];
        $video_id = $this->args[1];
        $this->_getHlsSize($client_id,$video_id);
        $this->out('Finished');
    }

    protected function _getHlsSize($client_id, $video_id)
    {
        $version_id = $this->VideoVersion->field('id',array('video_id'=>$video_id,'name'=>'HLS','ext'=>'m3u8'));
        $s3 = Aws::factory(AWS_OREGON)->get('S3');
        $iterator = $s3->getIterator('ListObjects',array(
            'Bucket' => MEDIA_HLS,
            'Prefix' => $client_id.'/'.$video_id.'/',
        ));
        $count = 0;
        $bytes = 0;
        foreach($iterator as $object) {
            $count++;
            $bytes = $bytes + $object['Size'];
        }
        if($version_id) {
            $this->out('VersionID exists - Updating filesize');
            $this->VideoVersion->id = $version_id;
            $this->VideoVersion->saveField('filesize',$bytes);
        }
        $this->out('Total objects: '.$count);
        $this->out('Total bytes: '.$bytes);
        return true;
    }
}
