<?php
use Aws\Common\Aws;
use Aws\S3\Sync\DownloadSyncBuilder;
App::uses('AppShell', 'Console/Command');

class FriendShell extends AppShell
{
    public $status = null;
    public $retry = 3;

    var $uses = array('Video');

    /*public function test()
    {
        $s3 = Aws::factory(AWS_CONFIG)->get('S3');
        $s3->registerStreamWrapper();
        
        $video_file = TMP.'downloads/995/videos/test1.mp4';
        //$video_file = TMP.'downloads/995/videos/test1.mov';

        $result = $s3->getObject(array(
            'Bucket' => 'media.fordela.com',

            // TODO: Seems filesize changes where it's found

            'Key'    => '2672/Hollywood_Babylon_Soft_Sample.mp4', // About 70MB
            //'Range'  => 'bytes=0-20000', // mp4
            'Range'  => 'bytes=0-2000000', // mov - 20MB or less 100000 works
            'SaveAs' => $video_file
        ));
        $mediainfo = $this->Video->mediainfo($video_file);
        debug($mediainfo);
    }*/

    public function rename()
    {
        $file1 = 'this is my file.mp4';
        $file_path = TMP.'downloads/995/videos/';
        $newfile = $file_path.'this_is_my_file.mp4';

        $this->out('Renaming file: '.$file1.' to '.$newfile);

        rename($file_path.$file1,$newfile);
        $this->out('Finished');
    }

    public function doSomething() 
    {
        $this->out('Doing something...');
        if(empty($this->args)) {
            $this->out("No args passed");die;
        }
        foreach($this->args as $val) {
            $this->out($val);
        }
        $this->status['status'] = 'Finished';
        $this->status['description'] = 'File Moved and Size Matches';
        $this->status['finished'] = date('Y-m-d H:i:s');
        $jobId = end($this->args);
        $this->JobQueue->updateJob($jobId,$this->status);
    }

    protected function initAws() 
    {
        return $this->Aws = Aws::factory(AWS_CONFIG)->get('S3');
    }

}
