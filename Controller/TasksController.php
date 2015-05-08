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
        // Only accept from allowed IPs
        if(!in_array(env('REMOTE_ADDR'), array('127.0.0.1'))) {
            $this->log($_SERVER,'request');
            die;
        }
    }

    // Test function for testing workers/jobs
    public function friend($function = 'doSomething')
    {
        // Get $params array from POST data
        $this->_queue('default','Friend',$function,array('Go','process','a','job',NOW));
    }

    public function index()
    {
    	//$this->_queue('default','Friend','doSomething',array('Go','process','a','job',NOW));
    }

    public function upload($model = 'video') {
        if($this->request->is('post')) {
            $this->log($this->request->data,'job_post');
            $this->_queue('file_mover','FileMover','copyToS3',$params);
        }
    }
}
