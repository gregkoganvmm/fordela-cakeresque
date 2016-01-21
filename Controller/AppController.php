<?php
/**
 * Application level Controller
 *
 * This file is application-wide controller file. You can put all
 * application-wide controller-related methods here.
 *
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       app.Controller
 * @since         CakePHP(tm) v 0.2.9
 */

App::uses('Controller', 'Controller');
App::uses('CakeEmail', 'Network/Email');

/**
 * Application Controller
 *
 * Add your application-wide methods in the class below, your controllers
 * will inherit them.
 *
 * @package		app.Controller
 * @link		http://book.cakephp.org/2.0/en/controllers.html#the-app-controller
 */
class AppController extends Controller {

    public $components = array(
        'Session',
    );

    /**
    *
    *  Application wide helpers
    *
    * @var array
    */
    var $helpers = array(
        'Html',
        'Form',
        'Text',
        //'TimeZones',
        'Number',
    );

    public function beforeFilter(){
        $auth = $this->Session->read('Access');
        if(!$auth && $this->here != '/job_queues/login'){
            $this->redirect(array('controller'=>'job_queues', 'action' => 'login'));
        }
    }

    /**
    * Sitewide Email Function
    * Writes emails to the database with dynamic variables for sending later.
    *
    *
    * @param string $subject Email Subject
    * @param mixed $body Email message, can be string or an array of variables to be set for view
    * @param string $to  Email address of recipient or array of recipients with to, cc, and bcc keys defined
    * @param string $from Email Address from, defaults to "Fordela VMS <support@fordela.com>", can be specified as just email address or proper format "John Smith <jsmith@example.com>"
    * @param string $template Email Template
    * @param string $viewVars Key=>Value pairs of Variables for the view
    * @param mixed $cc String or Array of CC email Addresses
    * @param mixed $bcc String or Array of BCC email Addresses
    *
    * @return null
    */

    public function _email($subject, $body = '', $to=null, $from=null, $template=null, $viewVars = array(),$cc=null,$bcc=null,$layout = 'default'){
        if(empty($from)){
            $from = array('no-reply@fordela.com' => 'Fordela' );
        }
        $email = new CakeEmail();

        $email->helpers(array('Html','TimeZones'));

        $template_vars = (!empty($viewVars) && is_array($viewVars))? serialize($viewVars) : NULL;
        $body = (is_array($body))? serialize($body) : $body;

        $email->config(array(
            'subject' => $subject,
            'message' => $body,
            'to' => $to,
            'from' => $from,
            'template' => $template,
            'viewVars' => $viewVars,
            'cc' => $cc,
            'bcc' => $bcc,
            'layout' => $layout,
            'emailFormat' => 'html'
        ));

        $email->sender('support@fordela.com', 'Fordela');

        //Log the message
        $message = compact('to','subject','from','body','template','template_vars','cc','bcc');
        if(is_array($message['cc'])){
            $message['cc'] = join(',',$message['cc']);
        }
        if(is_array($message['bcc'])){
            $message['bcc'] = join(',',$message['bcc']);
        }
        if(is_array($message['to'])){
            $message['to'] = join(',',$message['to']);
        }
        if(is_array($message['from'])){
            $message['from'] = join(',',$message['from']);
        }

        $this->log($message,'email_messages');

        if($email->send()){
            $this->log('Success','email_messages');
            return true;
        }
        else{
            return false;
        }
    }

    /**
    * Makes a Job For the Resque Server
    * Saves a copy to the JobQueue table for UI
    *
    * @param String $queue the name of the queue to add the job
    * @param String $shell the name of the shell script that will process the job
    * @param String $function the name of the function in the shell script to call
    * @param Array $params any params the function needs
    *
    **/
    function _queue($queue, $shell, $function, $params = array(), $jobId = null)
    {
        $this->JobQueue = ClassRegistry::init('JobQueue');
        //ad the shell function to run to the beginning of the params array
        array_unshift($params,$function);

        // Create database record
        $job = array(
            'queue'=>$queue,
            'type'=>$shell.'Shell',
            'function'=>$function,
            'params'=>serialize($params),
        );

        if (empty($jobId)) {
            // New Job
            $this->JobQueue->create($job);
            $this->JobQueue->save();
            $jobId = $this->JobQueue->id;
        } else {
            // Reset Job
            $this->JobQueue->id;
            $job['JobQueue']['status'] = 'Reset';
            $job['JobQueue']['description'] = '';
            $job['JobQueue']['finished'] = '';
            $this->JobQueue->save($job['JobQueue']);
        }

        array_push($params,$jobId);

        // Send job to JobQueue (Resque) server
        $redisJobId = CakeResque::enqueue($queue,$shell.'Shell',$params);
        //debug($redisJobId);die;
    }
}
