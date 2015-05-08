<?php

class SnippetsController extends AppController {

    public function index() {
        // TODO: Call $this->_queue($queue,$shell,$function,$params=array())
        $this->_queue('default','Friend','doSomething',array('John Doe','Ghana'));

        // CakeResque::enqueue(
        //     'default',
        //     'FriendShell',
        //     array('doSomething', 'John Doe', 'Ghana')
        // );
    }
}
