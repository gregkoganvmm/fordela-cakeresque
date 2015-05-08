<?php
App::uses('AppController', 'Controller');
/**
 * JobQueues Controller
 *
 * @property JobQueue $JobQueue
 */
class JobQueuesController extends AppController 
{

	public $paginate = array(
		'order' => array(
		    'JobQueue.id' => 'desc'
		)
	);

	public function task_log($log = null)
	{
		App::uses('Folder', 'Utility');
		App::uses('File', 'Utility');

		//$log_path = TMP.'logs'.DS.'php-resque-worker.log';
		$log_path = TMP.'logs'.DS.'resque-2015-05-07.log';
		$contents = array('Currently there are no logs to show');
		if(file_exists($log_path)) {
			$file = new File($log_path);
			$contents = $file->read();
			$contents = explode("\n", str_replace('*','',$contents));
			$contents = array_reverse($contents);
			$file->close();
		}
		$this->set('log', $contents);
	}

	/*public function job() {
		$this->autoRender = false;
		if( $this->request->is('post') ) {
			$this->log($this->request->data,'post_data');
		}
	}*/

	// MongoDB Test
	public function test() 
	{
		$this->autoRender = false;
		//debug('TEST1');

		$this->SessionActivities = ClassRegistry::init('Analytics.SessionActivity');
		$result = $this->SessionActivities->find('first',array('SessionActivity.id' => '4f611361476da9ec3fc4c2d3'));
		echo '<pre>';
		print_r($result);
		echo '</pre>';
		//debug($result);
		die;
	}

	public function login() 
	{
		if (!empty($this->request->data)) {
		    if ($this->request->data['JobQueue']['username'] == 'admin' && $this->request->data['JobQueue']['password'] == 'pa33word') {
			$this->Session->write('Access',1);
			$this->redirect(array('action' => 'index'));
		    } else {
			debug('Username or password is incorrect');
		    }
		}
	}

/**
 * index method
 *
 * @return void
 */
	public function index() 
	{
		$this->JobQueue->recursive = 0;
		if(isset($this->request->params['named']['show']) == 'unfinished'){
			$this->paginate = array(
				'conditions' => array('JobQueue.status <>' => 'Finished'),
				'order' => array('JobQueue.id' => 'desc')
			);
			$data = $this->paginate();
		}else{
			// Default desc and don't show preCache jobs
			$this->paginate = array(
				//'conditions' => array('JobQueue.function <>' => 'preCacheAnalytics'),
				'conditions' => array('NOT' => array(
					'JobQueue.function' => 'preCacheAnalytics',
					'OR' => array('JobQueue.function' => 'checkStat')
				)),
				'order' => array('JobQueue.id' => 'desc')
			);
			$data = $this->paginate();
		}
		$this->set('jobQueues', $data);
	}

/**
 * view method
 *
 * @throws NotFoundException
 * @param string $id
 * @return void
 */
	public function view($id = null) 
	{
		$this->JobQueue->id = $id;
		if (!$this->JobQueue->exists()) {
			throw new NotFoundException(__('Invalid job queue'));
		}
		$this->set('jobQueue', $this->JobQueue->read(null, $id));
	}

/**
 * add method
 *
 * @return void
 */
	public function add() 
	{
		if ($this->request->is('post')) {
			$this->JobQueue->create();
			if ($this->JobQueue->save($this->request->data)) {
				$this->Session->setFlash(__('The job queue has been saved'));
				$this->redirect(array('action' => 'index'));
			} else {
				$this->Session->setFlash(__('The job queue could not be saved. Please, try again.'));
			}
		}
	}

/**
 * edit method
 *
 * @throws NotFoundException
 * @param string $id
 * @return void
 */
	public function edit($id = null) 
	{
		$this->JobQueue->id = $id;
		if (!$this->JobQueue->exists()) {
			throw new NotFoundException(__('Invalid job queue'));
		}
		if ($this->request->is('post') || $this->request->is('put')) {
			if ($this->JobQueue->save($this->request->data)) {
				$this->Session->setFlash(__('The job queue has been saved'));
				$this->redirect(array('action' => 'index'));
			} else {
				$this->Session->setFlash(__('The job queue could not be saved. Please, try again.'));
			}
		} else {
			$this->request->data = $this->JobQueue->read(null, $id);
		}
	}

/**
 * delete method
 *
 * @throws MethodNotAllowedException
 * @throws NotFoundException
 * @param string $id
 * @return void
 */
	public function delete($id = null) 
	{
		if (!$this->request->is('post')) {
			throw new MethodNotAllowedException();
		}
		$this->JobQueue->id = $id;
		if (!$this->JobQueue->exists()) {
			throw new NotFoundException(__('Invalid job queue'));
		}
		if ($this->JobQueue->delete()) {
			$this->Session->setFlash(__('Job queue deleted'));
			$this->redirect(array('action' => 'index'));
		}
		$this->Session->setFlash(__('Job queue was not deleted'));
		$this->redirect(array('action' => 'index'));
	}

	public function reset($id = null,$page = 1, $unfinished = 0) {
		$this->JobQueue->id = $id;
		if (!$this->JobQueue->exists()) {
			throw new NotFoundException(__('Invalid job'));
		}

		$job = $this->JobQueue->read(null,$id);

		$queue = $job['JobQueue']['queue'];
		$shell = str_replace('Shell','',$job['JobQueue']['type']);
		$function = $job['JobQueue']['function'];

		$params = unserialize($job['JobQueue']['params']);
		unset($params[0]);

		$this->_queue($queue,$shell,$function,$params,$id);

		$show = ($unfinished <> 0) ? '/show:unfinished' : '';

		$this->Session->setFlash(__('Job reset'));
		$this->redirect('/job_queues/index/page:'.$page.$show);
	}
}
