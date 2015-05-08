<h2><?php echo __('Task Log'); ?></h2>
<h1><?php echo $this->Html->link('Tasks',array('controller'=>'job_queues', 'action'=>'index'));?></h1>
<table>
<?php
	foreach($logs as $log) {
		echo $this->Html->tableCells(array(
	        array($log['JobQueueLog']['datetime'].' | '.$log['JobQueueLog']['message']),
	    ));
	}
?>
</table>
<p>
<?php
	echo $this->Paginator->counter(array('format' => __('Page {:page} of {:pages}, showing {:current} records out of {:count} total, starting on record {:start}, ending on {:end}')));
?>
</p>
<div class="paging">
<?php
	echo $this->Paginator->prev('< ' . __('previous'), array(), null, array('class' => 'prev disabled'));
	echo $this->Paginator->numbers(array('separator' => ''));
	echo $this->Paginator->next(__('next') . ' >', array(), null, array('class' => 'next disabled'));
?>
</div>