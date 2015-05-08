<div class="jobQueues view">
<h2><?php  echo __('Job Queue'); ?></h2>
	<dl>
		<dt><?php echo __('Id'); ?></dt>
		<dd>
			<?php echo h($jobQueue['JobQueue']['id']); ?>
			&nbsp;
		</dd>
		<dt><?php echo __('Queue'); ?></dt>
		<dd>
			<?php echo h($jobQueue['JobQueue']['queue']); ?>
			&nbsp;
		</dd>
		<dt><?php echo __('Type'); ?></dt>
		<dd>
			<?php echo h($jobQueue['JobQueue']['type']); ?>
			&nbsp;
		</dd>
		<dt><?php echo __('Function'); ?></dt>
		<dd>
			<?php echo h($jobQueue['JobQueue']['function']); ?>
			&nbsp;
		</dd>
		<dt><?php echo __('Params'); ?></dt>
		<dd>
			<?php
				$params = unserialize($jobQueue['JobQueue']['params']);
				foreach($params as $i =>$param){
					echo ($i+1).') '. h($param).' </br>'; 
				}
			
			?>
			&nbsp;
		</dd>
		<dt><?php echo __('Worker'); ?></dt>
		<dd>
			<?php echo h($jobQueue['JobQueue']['worker']); ?>
			&nbsp;
		</dd>
		<dt><?php echo __('Progress'); ?></dt>
		<dd>
			<?php echo h($jobQueue['JobQueue']['progress']); ?>
			&nbsp;
		</dd>
		<dt><?php echo __('Status'); ?></dt>
		<dd>
			<?php echo h($jobQueue['JobQueue']['status']); ?>
			&nbsp;
		</dd>
		<dt><?php echo __('Description'); ?></dt>
		<dd>
			<?php echo h($jobQueue['JobQueue']['description']); ?>
			&nbsp;
		</dd>
		<dt><?php echo __('Failed'); ?></dt>
		<dd>
			<?php echo h($jobQueue['JobQueue']['failed']); ?>
			&nbsp;
		</dd>
		<dt><?php echo __('Created'); ?></dt>
		<dd>
			<?php echo h($jobQueue['JobQueue']['created']); ?>
			&nbsp;
		</dd>
		<dt><?php echo __('Fetched'); ?></dt>
		<dd>
			<?php echo h($jobQueue['JobQueue']['fetched']); ?>
			&nbsp;
		</dd>
		<dt><?php echo __('Finished'); ?></dt>
		<dd>
			<?php echo h($jobQueue['JobQueue']['finished']); ?>
			&nbsp;
		</dd>
	</dl>
</div>
<div class="actions">
	<h3><?php echo __('Actions'); ?></h3>
	<ul>
		<li><?php echo $this->Html->link(__('Edit Job Queue'), array('action' => 'edit', $jobQueue['JobQueue']['id'])); ?> </li>
		<li><?php echo $this->Form->postLink(__('Delete Job Queue'), array('action' => 'delete', $jobQueue['JobQueue']['id']), null, __('Are you sure you want to delete # %s?', $jobQueue['JobQueue']['id'])); ?> </li>
		<li><?php echo $this->Html->link(__('List Job Queues'), array('action' => 'index')); ?> </li>
		<li><?php echo $this->Html->link(__('New Job Queue'), array('action' => 'add')); ?> </li>
	</ul>
</div>
