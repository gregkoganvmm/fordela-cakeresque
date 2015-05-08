<?php

class TokenShell extends Shell
{
    public $uses = array('Token','JobQueue');
    
    public function perform()
    {
        $this->initialize();
        $this->{array_shift($this->args)}();
    } 
    /**
     * Sames a BS record as a test to Token Table
     *
     * @param String param1
     * @param String param2
     * @param Int JobId
     *
     **/
    
    public function task1(){
        
        
        $token = array(
            //'passcode'=>'shit',
            'passcode' => $this->args[0].':'.$this->args[1],
            'user_id'=>1004,
            'client_id' => 1004
        );
              
        $this->Token->create($token);
        
        if($this->Token->save()){
            $status['description'] = 'Token Record saved';
            $status['status'] = 'Finished';
            $status['finished'] = date('Y-m-d H:i:s');
        }
        else{
            $status['description'] = 'Token Record failed to saved';
            $status['status'] = 'Error';
            $status['failed'] = true;
        }

        //update JobQueue record
        $jobId = array_pop($this->args);
        $this->JobQueue->id = $jobId;
        $this->JobQueue->save($status);
        
    }
}
?>