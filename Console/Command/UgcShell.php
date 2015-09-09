<?php
App::uses('HttpSocket','Network/Http');

App::uses('ComponentCollection','Controller');
App::uses('AwsComponent','Controller/Component');

class UgcShell extends Shell {

    var $status = array();

    public $uses = array('JobQueue');

    public function perform() {
        $this->initialize();
        $this->{array_shift($this->args)}();
    }

    public function processUgc()
    {
        $srcFile = $this->args[0];

        $this->downloadFile($srcFile);
        $this->srcToS3($srcFile);

        //Mark Job finished
        $this->status['status'] = 'Finished';
        $this->status['description'] = 'UGC file copied to S3';
        $this->status['finished'] = date('Y-m-d H:i:s');
        $jobId = end($this->args);
        $this->JobQueue->updateJob($jobId,$this->status);

        //POST back to VMS /videos/ugc to kick off encoding job
        $HttpSocket = new HttpSocket();

        $data = array('Ugc' => array(
            'processUgc' => true,
            'filename' => $srcFile
        ));

        $results = $HttpSocket->post(ENVIRONMENT_APP_URL.'/ugc/process', $data);
    }

    // Make AwsComponent callable
    protected function _initAws() {
        $Collection = new ComponentCollection();
        return $this->Aws = new AwsComponent($Collection);
    }

    protected function srcToS3($srcFile)
    {
        //Use putObject to archive.fordela.com/ugc/FILENAME
        $this->_initAws();
        $this->Aws->get('S3','putObject',array(
            'Bucket' => 'archive.fordela.com',
            'Key' => 'ugc/'.$srcFile,
            'SourceFile' => TMP.'uploads/ugc/'.$srcFile
        ));
        return;
    }

    protected function downloadFile ($srcFile)
    {
        //URL path always the same except for node_id named mp4
        $url = 'http://www.3dvisionlive.com/sites/default/files/ugc/free/'.$srcFile;
        $path = TMP .'uploads'.DS.'ugc'.DS.$srcFile;


        $newfname = $path;
        $file = fopen ($url, "rb");
        if ($file) {
            $newf = fopen ($newfname, "wb");

            if ($newf)
                while(!feof($file)) {
                    fwrite($newf, fread($file, 1024 * 8 ), 1024 * 8 );
                }
        }

        if ($file) {
            fclose($file);
        }

        if ($newf) {
            fclose($newf);
        }

        return;
    }

}