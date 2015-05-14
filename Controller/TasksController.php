<?php
App::uses('AppController', 'Controller');
/**
* Api Controller
* 
* Function name should probably be the endpoint per shell. Depending on the shell can
* either pre define the function to be run or can pass it along with the params in the 
* post data.
*
* @property Api $Api
*/
class TasksController extends AppController 
{

    var $autoRender = false;

    public function beforeFilter()
    {
        // Only accept from allowed IPs -> localhost, office, vms
        if(!in_array(env('REMOTE_ADDR'), array('127.0.0.1','108.252.137.163'))) {
            die;
        }
    }

    // Test function for testing workers/jobs
    public function friend()
    {
        $this->_queue('default','Friend','doSomething',array('Go2','process','a','job',NOW));
        /*if($this->request->is('post') && is_array($this->request->data['params'])) {
            $params = $this->request->data['params'];
            $this->_queue('default','Friend','doSomething',$params);
        }*/
    }

    /**
     * Default endpoint
     */
    public function index()
    {
    	// do nothing
    }

    /**
     * FileMover endpoint
     * 
     */ 
    public function upload()
    {
        if($this->request->is('post') && is_array($this->request->data['params'])) {
            $params = $this->request->data['params'];
            $this->_queue('file_mover','FileMover','copyToS3',$params);
        }
    }

    /**
     * Analytics endpoint
     */
    public function analytics()
    {
        if($this->request->is('post') && is_array($this->request->data['params'])) {
            $params = $this->request->data['params'];
            $this->_queue('default','Analytics','DailyDigest',$params);
        }
    }
}
