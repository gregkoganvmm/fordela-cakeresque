<?php
// Temporary Shell for copying old video to Stellar client bucket
// before deleting
use Aws\Common\Aws;

class StellarShell extends Shell {

  var $uses = array('Video');

  var $video_total = 0;

  public function main() {
    $this->_initS3();
    $videos = $this->_getVideoList();
    $this->video_total = count($videos);
    $this->out('Total video count is '.$this->video_total);
    $this->_copyTo($videos); // Copy Source to bucket stellaruploads
  }

  protected function _initS3() {
    $this->S3 = Aws::factory(AWS_CONFIG)->get('S3');
    $this->S3->registerStreamWrapper();
  }

  protected function _copyTo($videos) {
    $count = 0;
    foreach($videos as $k => $video) {
      $count++;
      $this->out($count.' of '.$this->video_total);

      $destination = 's3://stellaruploads/source/'.urlencode($video['Video']['archive']);
      $source = str_replace('s3://','',$video['Video']['location']);
      $newfile = urlencode($video['Video']['archive']);

      // Copy file from archive to stellaruploads if not already there
      if(file_exists('s3://'.$source) && !file_exists($destination)) {
        $this->out('VideoID: '.$video['Video']['id'].' | Copying file '.$video['Video']['archive'].' | Size: '.$video['Video']['filesize']);
        $this->S3->CopyObject(array(
          'Bucket' => 'stellaruploads',
          'CopySource' => $source,
          'Key' => 'source/'.$newfile,
          'ACL' => 'public-read'
        ));

        // Does filesize on stellaruploads match original archive filesize
        if(filesize('s3://'.$source) == filesize($destination)) {
          $this->out('Filesize match on file '.$video['Video']['archive']);
        } else {
          $this->out('Something is not right for video_id '.$video['Video']['id']);
          $this->log('Something is not right for video_id '.$video['Video']['id'],'stellar');
        }
      } else if(!file_exists($video['Video']['location'])) {
        $this->out('File does not exist on archive - VideoID: '.$video['Video']['id'].' - '.$source);
        $this->log('File does not exist on archive - VideoID: '.$video['Video']['id'].' - '.$source,'stellar');
      } else {
        $this->out('File already copied'.$video['Video']['id'].' - '.$video['Video']['archive']);
      }
    }
  }

  /*
  * Get Video list
  */
  protected function _getVideoList() {
    return $this->Video->find('all', array(
      'conditions' => array(
        'Video.client_id' => 1068,
        'Video.modified <' => '2012-04-17 00:00:01',
        'Video.active' => 1,
        'Video.deleted' => 0
      ),
      'fields' => array('Video.id','Video.location','Video.archive','Video.archive_dir','filesize')
    ));
  }
}
?>
