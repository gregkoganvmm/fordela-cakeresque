<?php
use Aws\Common\Aws;
use Aws\S3\Sync\DownloadSyncBuilder;
App::uses('AppShell', 'Console/Command');

class FriendShell extends AppShell
{
    public $status = null;
    public $retry = 3;

    /*public function perform() {
        $this->initialize();
        $this->{array_shift($this->args)}();
    }*/

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
