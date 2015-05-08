<?php
App::uses('Component','Controller');
use Aws\Common\Aws;
use Aws\Common\Enum\Size;
use Aws\Common\Exception\MultipartUploadException;
use Aws\S3\Model\MultipartUpload\UploadBuilder;

class AwsComponent extends Component {

    var $config = null;

    protected function initAws($service) {
        if(!empty($this->config)) {
            return $this->Aws = Aws::factory(APP.'Config'.DS.$this->config.'.php')->get($service);
        }
        return $this->Aws = Aws::factory(AWS_CONFIG)->get($service);
    }

    /**
     * Shortcut function preventing the need to repeat setting the service
     * and then making a method call with arguments.  Can have a returned array
     * result in one line instead of multiple.
     *
     */
    public function get($service = null, $method = null, $args = null, $config = null) {
        $this->config = $config;
        $this->initAws($service);
        $result = $this->Aws->{$method}($args);
        return $result->toArray();
    }

    /**
     * Shortcut call for registering an S3 stream wrapper
     */
    public function registerStreamWrapper() {
        $this->initAws('S3');
        return $this->Aws->registerStreamWrapper();
    }

    /**
     * Create a signed URL from the command object (querystring)
     *
     * @param $type - S3 or CloudFront - TODO: Need to setup to work for Cloudfront
     * @param $bucket
     * @param $key
     * @param string $expires
     * @return mixed
     */
    public function signUrl($type,$bucket,$key,$expires = '+4 hours') {
        $this->initAws($type);
        if($type == 'S3') {
            $command = $this->Aws->getCommand('GetObject', array(
                'Bucket' => $bucket,
                'Key' => $key
            ));
            $signedUrl = $command->createPresignedUrl($expires);
        } else {
            /**
             * CloudFront
             *   NOTE: $bucket variable will look like the following examples for CloudFront:
             *   - 'rtmp://example-distribution.cloudfront.net'
             *   - 'http://example-distribution.cloudfront.net'
             */
            $signedUrl = $this->Aws->getSignedUrl(array(
                'url' => $bucket.'/'.$key,
                'expires' => strtotime($expires)
            ));
        }

        return $signedUrl;
    }

    /**
     * Multipart Upload
     *
     * @param $source
     * @param $bucket
     * @param $key
     * @param array $options
     */
    public function mpu($source,$bucket,$key,$options = array()) {
        // Get the S3 client
        $this->initAws('S3');
        $acl = (isset($options['acl'])) ? $options['acl'] : 'private';

        // Create a transfer object from the builder
        $transfer = UploadBuilder::newInstance()
            ->setClient($this->Aws)         // An S3 client
            ->setSource($source)            // Can be a path, file handle, or EntityBody object
            ->setBucket($bucket)            // Your bucket
            ->setKey($key)                  // Your desired object key
            ->setMinPartSize(15 * Size::MB)  // Minimum part size to use (at least 5 MB)
            ->setConcurrency(5)             // Number of concurrent uploaded parts (optional)
            ->setOption('ACL',$acl)         // ACL defaults to private unless passed in options array
            ->setOption('ServerSideEncryption','AES256')
            ->build();

        try {
            $transfer->upload();
            return true;
        } catch (MultipartUploadException $e) {
            //echo $e->getMessage() . "\n";
            $transfer->abort();
            return false;
        }
    }
}

