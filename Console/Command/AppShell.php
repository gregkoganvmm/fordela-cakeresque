<?php
/**
 * AppShell file
 *
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         CakePHP(tm) v 2.0
 */

App::uses('Shell', 'Console');
//App::uses('AppModel', 'Model');

/**
 * Application Shell
 *
 * Add your application-wide methods in the class below, your shells
 * will inherit them.
 *
 * @package       app.Console.Command
 */
class AppShell extends Shell {

    public $uses = array('JobQueue');

    public function perform() {
        $this->initialize();
        $this->loadTasks();
        //$this->{array_shift($this->args)}();
        return $this->runCommand($this->args[0], $this->args);
        
    }

}
