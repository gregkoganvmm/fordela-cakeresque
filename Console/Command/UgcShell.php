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

    // ftp://nvidiahd:nvidiahd123@nvidiahd.upload.akamai.com/112890/progressive/ugc/{$node->nid}.mp4
    public function pushToAkamai()
    {
        // Need node_id (in filename)
        $version = $this->args[0];

        $this->_initAws();
        //GetObject from media.fordela.com/ugc/FILENAME
        $this->Aws->get('S3','getObject',array(
            'Bucket' => 'media.fordela.com',
            'Key' => 'ugc/'.$version,
            'SaveAs' => TMP.'uploads/ugc/version/'.$version
        ));

        // connect and login to FTP server
        $ftp_server = "nvidiahd.upload.akamai.com";
        $ftp_username = 'nvidiahd';
        $ftp_userpass = 'nvidiahd123';

        $ftp_conn = ftp_connect($ftp_server) or die("Could not connect to $ftp_server");
        $login = ftp_login($ftp_conn, $ftp_username, $ftp_userpass);
        ftp_pasv($ftp_conn, true);
        ftp_chdir($ftp_conn, "112890/progressive/ugc");

        $file = TMP.'uploads/ugc/version/'.$version;

        // upload version
        if (ftp_put($ftp_conn, $version, $file, FTP_ASCII)) {
            echo "Successfully uploaded $file.";
        } else {
            echo "Error uploading $file.";
        }

        // close connection
        ftp_close($ftp_conn);

        $info = pathinfo($version);

        //TODO: On Successful upload notify 3DVL & cleanup
        //The following is copied from the encoder git repo.  Need to modify slightly for our purposes here.
        //Drupal expects: reference, status, source
        $notification = (object) array(
            //'source'=>'http://hdprogressive.3dvisionlive.com/ugc/'.$version, //TODO: Is this correct?
            'source'=>'http://www.3dvisionlive.com/sites/default/files/ugc/free/'.$version, //TODO: Is this correct?
            'status'=> 'Finished',
            'reference'=>$info['filename']
        );

        // XML Helper obsolete w/ Cake 2.0 - Use JSON instead
        //$post = json_encode($notification);
        $this->log($notification,'postLog');
        $http = new HttpSocket();
        $http->post('http://www.3dvisionlive.com/fordela_encoder/notification', $notification);


        //Mark Job finished
        $this->status['status'] = 'Finished';
        $this->status['description'] = 'UGC version copied to Akamai';
        $this->status['finished'] = date('Y-m-d H:i:s');
        $jobId = end($this->args);
        $this->JobQueue->updateJob($jobId,$this->status);
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
