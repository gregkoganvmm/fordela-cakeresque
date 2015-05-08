<h2><?php echo __('Task Log'); ?></h2>
<h1><?php echo $this->Html->link('Tasks',array('controller'=>'job_queues', 'action'=>'index'));?></h1>
<table>
<?php
    foreach($log as $message){
        echo $this->Html->tableCells(array(
            array($message),
        ));
    }
?>
</table>