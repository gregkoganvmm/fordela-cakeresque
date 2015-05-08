<div class="jobQueues form">
<?php echo $this->Form->create('JobQueue'); ?>
	<fieldset>
		<legend><?php echo __('Add Job Queue'); ?></legend>
	<?php
		echo $this->Form->input('queue');
		echo $this->Form->input('type');
		echo $this->Form->input('function');
		echo $this->Form->input('params');
		echo $this->Form->input('worker');
		echo $this->Form->input('progress');
		echo $this->Form->input('status');
		echo $this->Form->input('description');
		echo $this->Form->input('failed');
		echo $this->Form->input('fetched');
		echo $this->Form->input('finished');
	?>
	</fieldset>
<?php echo $this->Form->end(__('Submit')); ?>
</div>
<div class="actions">
	<h3><?php echo __('Actions'); ?></h3>
	<ul>

		<li><?php echo $this->Html->link(__('List Job Queues'), array('action' => 'index')); ?></li>
	</ul>
</div>
