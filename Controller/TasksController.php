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
        $allowed = array(
            '127.0.0.1', // Self
            '108.252.137.163', // Office AT&T
            '199.83.220.241', // Office MonkeyBrain
            '54.191.119.250' // VMSAPP
        );
        if(!in_array(env('REMOTE_ADDR'), $allowed)) {
            //$this->log($_SERVER,'request');
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
    	// Enqueue if all is passed in POST data
        if(
            $this->request->is('post') && 
            isset($this->request->data['queue']) && 
            is_array($this->request->data['params']) && 
            isset($this->request->data['shell']) &&
            isset($this->request->data['function'])
        ) {
            // args: queue, shell, function, params
            $this->_queue($this->request->data['queue'], $this->request->data['shell'], $this->request->data['function'], $this->request->data['params']);
        }
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

    /**
     * login notification endpoint
     * 
     * Trigger sending an email to Client Contact or Content Manager
     */
    public function login_notification()
    {
        if($this->request->is('post') && is_array($this->request->data['params'])) {
            $params = $this->request->data['params'];
            $this->_queue('default','RandomTask','login_notification',$params);
        }
    }
}
