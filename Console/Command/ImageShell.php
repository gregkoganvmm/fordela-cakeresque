<?php
define('IMPORT_DIR',APP.DS.'tmp'.DS.'uploads'.DS.'img'.DS);
define('IMG_BUCKET','vms-images.fordela.com');
use Aws\Common\Aws;

App::uses('HttpSocket','Network/Http');
App::uses('Folder', 'Utility');
App::import('Vendor', 'SmartImage', array('file' => 'SmartImage.class.php'));

class ImageShell extends Shell {

    var $uses = array('Client','Video','Image','Playlist','EncodingProfile','AssetImage','JobQueue');

    var $model = 'Video'; // default - can override via args[0]
    var $model_path = null;

    public function perform() {
        $this->initialize();
        $this->{array_shift($this->args)}();
    }

    /**
     * Using to output available commands and options
     */
    public function main() {
        $this->out('Available commands:');
        $this->out('image get MODEL (values: client, image, playlist, profile, if blank defaults to video)');
        $this->out('image populate (Only populates Video thumbnails)');
        $this->out('image copyToS3 FOLDER (All if no arg passed. Optional argument values: clients, playlists, videos, x, or watermark)');
        $this->hr();
    }

    /**
     * Transfers thumbs only.  Originals bucket is a different region - Can we
     * use command to copyObjects from region to region?
     *
     */
    public function clientImageTransfer() {
        $OldVideoId = $this->args[0];
        $NewVideoId = $this->args[1];

        $s3 = Aws::factory(APP.'Config'.DS.'fordela-aws-oregon-config.php')->get('S3');
        // Source Images
        $srcList = $s3->listObjects(array(
            'Bucket' => 'vms-images.fordela.com',
            'Prefix' => 'videos/'.$OldVideoId
        ));
        if (count($srcList['Contents']) > 0) {
            $srcList = (array) $srcList['Contents'];
        } else {
            $srcList = array();
        }

        // Thumb Images
        $thumbList = $s3->listObjects(array(
            'Bucket' => 'vms-images.fordela.com',
            'Prefix' => 'x/videos/'.$OldVideoId
        ));
        if (count($thumbList['Contents']) > 0) {
            $thumbList = (array) $thumbList['Contents'];
        } else {
            $thumbList = array();
        }

        $list = array_merge($srcList,$thumbList);

        $commands = array();
        foreach($list as $prefix) {
            if(empty($prefix['Key'])) {
                continue;
            }
            $i = pathinfo($prefix['Key']);
            $key = ( substr($prefix['Key'],0,1) === 'x')
                ? 'x/videos/'.$NewVideoId.'/'.$i['basename']
                : 'videos/'.$NewVideoId.'/'.$i['basename'];

            $commands[] = $s3->getCommand('CopyObject',array(
                            'Bucket' => 'vms-images.fordela.com',
                            'CopySource' => 'vms-images.fordela.com/'.$prefix['Key'],
                            'Key' => $key,
                            'ACL' => 'public-read'
                        ));
        }

        $s3->execute($commands);

        // Get image1 value from old video id
        $this->Video->id = $OldVideoId;
        $oldVideoImage1 = $this->Video->field('image1');

        // Update new video record image_key value
        $this->Video->id = $NewVideoId;
        $newVideoImageKey = $this->Video->saveField('image_key','videos/'.$NewVideoId.'/'.$oldVideoImage1);

        // Make new AssetImage records under new video_id
        $this->AssetImage->copyRecordsToId($OldVideoId,$NewVideoId);

        // Update JobQueue job
        $this->status = array(
            'status' => 'Finished',
            'description' => 'Images and thumbs copied from client transfer',
            'finished' => date('Y-m-d H:i:s')
        );
        $jobId = end(array_values($this->args));
        $this->JobQueue->updateJob($jobId,$this->status);
    }


    /**
    * For AWS Elastic Trancoder videos
    *
    * TODO: Can we use this code for Zencoder images? Zencoder images
    * would need to end up on S3. Currently FTP'd to image server
    */
    public function processVideoImages() {
        // Set client_id and video_id
        $client_id = $this->args[0];
        $video_id = $this->args[1];

        // Init media bucket and get list
        $this->S3 = Aws::factory(APP.'Config'.DS.'fordela-aws-config.php')->get('S3');
        $list = $this->S3->listObjects(array('Bucket' => 'media.fordela.com','Prefix' => $client_id.'/images/'.$video_id));

        // Make VideoID directory. For each image download and make record
        $dir = new Folder(IMPORT_DIR.'videos'.DS.$video_id, true, 0775);
        $timestamp = time();
        foreach($list['Contents'] as $object) {
            // Set filename w/ timestamp. Download image
            $pathinfo = pathinfo($object['Key']);
            //$filename = $pathinfo['filename'].'-'.$timestamp.'.'.$pathinfo['extension'];
            $filename = $pathinfo['filename'].'.'.$pathinfo['extension'];
            $this->S3->getObject(array(
                'Bucket' => 'media.fordela.com',
                'Key' => $object['Key'],
                'SaveAs' => IMPORT_DIR.'videos'.DS.$video_id.DS.$filename
            ));
            // Set up for AssetImage record and _generateThumbs
            $this->model_path = 'videos';
            // Add AssetImage record
            $image_path = IMPORT_DIR.$this->model_path.DS.$video_id.DS.$filename;
            $this->AssetImage->addImage($image_path,'Video',$video_id);

            // Generate thumbs
            $this->_generateThumbs($video_id,$filename);
        }

        // Init Oregon, upload images (source and thumbs) to S3
        $this->initAws();
        $this->_copyToS3('videos/'.$video_id);
        $this->_copyToS3('x/videos/'.$video_id);

        // Set Video record image1 field
        $this->_updateImage1Field($video_id, $list['Contents']);

        // Update JobQueue job
        $this->status = array(
            'status' => 'Finished',
            'description' => 'Images and thumbs moved to S3',
            'finished' => date('Y-m-d H:i:s')
        );
        $jobId = end(array_values($this->args));
        $this->JobQueue->updateJob($jobId,$this->status);
    }

    protected function _updateImage1Field($video_id, $list) {
        $count = count($list);
        if($count >= 1) {
            $middle = round($count / 2) - 1;
            settype($middle,"integer");
            $imgInfo = pathinfo($list[$middle]['Key']);
            $image1 = $imgInfo['filename'].'.'.$imgInfo['extension'];
            $this->Video->id = $video_id;
            $this->Video->set(array(
                'image1' => $image1,
                'image_key' => 'videos/'.$video_id.'/'.$image1
            ));
            $this->Video->save();
        }
        return;
    }


    /**
     * Get list of images and import them
     *
     */
    public function get() {
        if(!empty($this->args[0])) {
            $this->model = ucfirst($this->args[0]);
        }
        $this->setModelPath();
        $list = $this->getList();
        $this->getFiles($list);
    }

    /**
     * Get list of images and import them
     *
     */
    public function getImages() {
        $this->model = ucfirst('image');
        $this->setModelPath();
        // Get list
        $list = $this->Image->find('all',array(
            'conditions' => array('Image.active' => 1,'Image.deleted' => 0),
            'fields' => array('Image.id','Image.client_id','Image.filename'),
            'contain' => array()
        ));

        // Save from S3
        $s3 = Aws::factory(AWS_CONFIG)->get('S3');
        $s3->registerStreamWrapper();
        foreach($list as $k => $image) {
            if(file_exists('s3://archive.fordela.com/'.$image['Image']['client_id'].'/images/'.$image['Image']['filename'])) {
                $dir = new Folder(IMPORT_DIR.'images'.DS.$image['Image']['id'],true,0775);
                $image_path = IMPORT_DIR.'images'.DS.$image['Image']['id'].DS.$image['Image']['filename'];
                $s3->getObject(array(
                    'Bucket' => 'archive.fordela.com',
                    'Key' => $image['Image']['client_id'].'/images/'.$image['Image']['filename'],
                    'SaveAs' => $image_path
                ));
                // Create AssetImage record
                $added = $this->AssetImage->addImage($image_path,'Image',$image['Image']['id']);
                $this->out($image_path);
                $out = ($added) ? 'AssetImage record created' : '***** Error saving AssetImage record *****';
                $this->out($out);
                $this->hr();
            } else {
                $this->out('ImageID: '.$image['Image']['id'].' '.$image['Image']['filename'].' - does not exist');
            }
        }
        $this->out('Finished');
    }

    public function populateImages() {
        // Get list
        $this->model = 'Image';
        $this->model_path = 'images';
        // Get list
        $list = $this->Image->find('all',array(
            'conditions' => array('Image.active' => 1,'Image.deleted' => 0),
            'fields' => array('Image.id','Image.client_id','Image.filename'),
            'contain' => array()
        ));

        foreach($list as $image) {
            $image_path = TMP.'uploads'.DS.'img'.DS.'images'.DS.$image['Image']['id'].DS.$image['Image']['filename'];
            //debug($image_path);die;
            if(file_exists($image_path)) {
                $sizes = array('320X240','120X76','80X45','120X76');
                $src = basename($image_path);
                $dir = new Folder(IMPORT_DIR.'x'.DS.$this->model_path.DS.$image['Image']['id'], true, 0775);
                foreach($sizes as $k => $size) {
                    $s = explode('X',$size);
                    $width = $s[0];
                    $height = $s[1];
                    $cut = false;
                    $img = new SmartImage($image_path);
                    if($k <> 3 && !file_exists(IMPORT_DIR.'x'.DS.$this->model_path.DS.$image['Image']['id'].DS.$width.'X'.$height.'_'.$src)) {
                        $img->resize($width, $height, $cut);
                        $img->saveImage(IMPORT_DIR.'x'.DS.$this->model_path.DS.$image['Image']['id'].DS.$width.'X'.$height.'_'.$src, 85);
                    }
                    if($k == 3 && !file_exists(IMPORT_DIR.'x'.DS.$this->model_path.DS.$image['Image']['id'].DS.'120X76_cut_'.$src)) {
                        $cut = true;
                        $img->resize($width, $height, $cut);
                        $img->saveImage(IMPORT_DIR.'x'.DS.$this->model_path.DS.$image['Image']['id'].DS.'120X76_cut_'.$src, 85);
                    }
                    $img->close();
                    $this->out($size.' ok');
                }
            } else {
                $this->out('Skip');
            }
        }
    }


    /**
     * Populate or generate Video thumb images
     */
    public function populate() {
        $this->setModelPath($model);
        $list = $this->getList();
        $this->generateThumbs($list);
    }

    /**
     * Sync directory or directory to S3
     *
     * Syncs all unless individual argument is passed
     */
    public function copyToS3() {
        $prefixes = (!empty($this->args[0])) ? array($this->args[0]) : array('clients','playlists','videos','x','watermark','images');
        $this->initAws();
        $this->out('Copying to S3');
        foreach($prefixes as $prefix) {
            $this->hr(1);
            $this->out($prefix);
            $this->hr(1);
            $this->_copyToS3($prefix);
        }
        $this->out('Finished');
    }






    /////////////////////

    protected function initAws() {
        // Use config extended for Oregon
        return $this->S3 = Aws::factory(APP.'Config'.DS.'fordela-aws-oregon-config.php')->get('S3');
    }

    protected function _copyToS3($prefix) {
        $dir = IMPORT_DIR.$prefix;
        return $this->S3->uploadDirectory($dir, IMG_BUCKET, $prefix, array(
            'params'      => array('ACL' => 'public-read'),
            'concurrency' => 20,
            'debug'       => true
        ));
    }

    /**
     * Sends a get request for each image to generate all thumbs for each asset
     *
     * @param $list = array
     * @return bool|int
     *
     * TODO: Update where images get imported to uploads dir as we
     * no longer need to generate based on http request
     */
    protected function generateThumbs($list) {
        if(empty($list)) {
            return $this->out('Empty list');
        }
        $count = count($list);
        $i = 0;
        foreach($list as $foreign_id => $image) {
            $i++;
            $this->out($i.' - '.$count.' -> '.$image);
            $image_path = IMPORT_DIR.$this->model_path.DS.$foreign_id.DS.$image;
            if(file_exists($image_path)) {
                $this->_generateThumbs($foreign_id,$image);
            } else {
                $this->out($image.' *** NOT OK ***');
            }
            $this->hr();
        }
        return $this->out('Finished');
    }

    protected function _generateThumbs($foreign_id,$image) {
        // TODO: Add additional thumb sizes to be generated - Not doing 120X90
        $sizes = array('320X240','120X76','80X45','120X76');
        $image_path = IMPORT_DIR.$this->model_path.DS.$foreign_id.DS.$image;
        $src = basename($image_path);
        $dir = new Folder(IMPORT_DIR.'x'.DS.$this->model_path.DS.$foreign_id, true, 0775);
        foreach($sizes as $k => $size) {
            $s = explode('X',$size);
            $width = $s[0];
            $height = $s[1];
            $cut = false;
            $img = new SmartImage($image_path);
            if($k <> 3 && !file_exists(IMPORT_DIR.'x'.DS.$this->model_path.DS.$foreign_id.DS.$width.'X'.$height.'_'.$src)) {
                $img->resize($width, $height, $cut);
                $img->saveImage(IMPORT_DIR.'x'.DS.$this->model_path.DS.$foreign_id.DS.$width.'X'.$height.'_'.$src, 85);
            }
            if($k == 3 && !file_exists(IMPORT_DIR.'x'.DS.$this->model_path.DS.$foreign_id.DS.'120X76_cut_'.$src)) {
                $cut = true;
                $img->resize($width, $height, $cut);
                $img->saveImage(IMPORT_DIR.'x'.DS.$this->model_path.DS.$foreign_id.DS.'120X76_cut_'.$src, 85);
            }
            $img->close();
            $this->out($size.' ok');
        }
        return;
    }

    /**
     * Loops through list of source images and after verifying the image
     * exists, it saves each image to a directory
     *
     * @param $list = array
     * @return bool|int
     */
    protected function getFiles($list) {
        if(empty($list)) {
            return $this->out('Empty list');
        }
        $url_path = 'http://'.IMG_SERVER.'/img/'.$this->model_path.'/';
        $count = count($list);
        $i = 0;
        foreach($list as $foreign_id => $image) {
            $i++;
            $this->out($i.' - '.$count);
            $image_path = IMPORT_DIR.$this->model_path.DS.$foreign_id.DS.$image;

            // Import image if not already imported
            if(!file_exists($image_path)) {
                $http = new HttpSocket();
                $response = $http->get($url_path.$image);
                $this->out($image.' '.$response->code);
                if($response->code == 200) {
                    $dir = new Folder(IMPORT_DIR.$this->model_path.DS.$foreign_id, true, 0755);
                    $f = fopen($image_path, 'w');
                    $http->setContentResource($f);
                    $http->get($url_path.$image);
                    fclose($f);

                    // Get image info and create image record
                    $added = $this->AssetImage->addImage($image_path,$this->model,$foreign_id);
                    $out = ($added) ? 'AssetImage record created' : '***** Error saving AssetImage record *****';
                    $this->out($out);
                    $this->hr();
                }
            }
        }
        return $this->out('Finished');
    }

    /**
     * Returns a list of source images using the model_id as the array key
     *
     * @return array
     */
    protected function getList() {
        switch($this->model) {
            case 'Client':
                $list = $this->Client->find('list',array(
                    'conditions' => array('Client.active' => 1,'Client.custom_logo <>' => ''),
                    'fields' => array('Client.id','Client.custom_logo')
                ));
                break;

            case 'Image':
                // Unlike other cases this is used only for thumb gen the first time
                $list = $this->AssetImage->find('list',array(
                    'conditions' => array('AssetImage.model' => 'Image'),
                    'fields' => array('AssetImage.foreign_id','AssetImage.filename'),
                    'order' => 'AssetImage.foreign_id ASC'
                ));
                break;

            case 'Playlist': // Only active where logo is not empty
                $list = $this->Playlist->find('list',array(
                    'conditions' => array('Playlist.active' => 1, 'Playlist.logo <>' => ''),
                    'fields' => array('Playlist.id','Playlist.logo'),
                ));
                break;

            case 'Background': // Only active where logo is not empty
                $list = $this->Playlist->find('list',array(
                    'conditions' => array('Playlist.active' => 1, 'Playlist.background <>' => ''),
                    'fields' => array('Playlist.id','Playlist.background'),
                ));
                break;

            case 'Profile':
                $list = $this->EncodingProfile->find('list',array(
                    'conditions' => array('EncodingProfile.watermark_source <>' => ''),
                    'fields' => array('EncodingProfile.id','EncodingProfile.watermark_source')
                ));
                break;

            default: // Video
                $list = $this->Video->find('list',array(
                    'conditions' => array(
                        'Video.image1 <>' => '',
                        'Video.id >=' => 38052
                        // Following conditions for testing only
                        //'Video.active' => 1,
                        //'Video.deleted' => 0,
                        //'Video.client_id' => 995
                    ),
                    'fields' => array('Video.id','Video.image1'),
                ));
        }

        return $list;
    }

    /**
     * Sets the model path for assets. Note: Not all model paths are plural or
     * even reference their model
     */
    protected function setModelPath() {
        switch($this->model) {
            case 'Profile': $this->model_path = 'watermark'; break;
            case 'Audio': $this->model_path = 'audio'; break;
            default: $this->model_path = strtolower($this->model).'s';
        }
        return;
    }
}

/*
// SCHEMA
CREATE TABLE `asset_images` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `model` varchar(24) DEFAULT NULL,
  `foreign_id` int(11) NOT NULL,
  `filename` varchar(255) DEFAULT NULL,
  `ext` varchar(6) NOT NULL DEFAULT 'gif',
  `key` varchar(255) DEFAULT NULL,
  `filesize` int(11) DEFAULT NULL,
  `height` int(4) DEFAULT NULL,
  `width` int(4) DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
*/
?>
