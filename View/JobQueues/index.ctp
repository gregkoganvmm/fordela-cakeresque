<style>
div.index { 
	float: none; 
	border-left: 0px;
	padding: 10px 0%;
	width: 100%;
}
div.actions2 li { display: inline; }
div.actions2 ul li { margin: 0 5px; }
</style>
<div class="actions2">
	<h3><?php echo __('Actions'); ?></h3>
	<ul>
		<li>
			<?php 
				if(isset($this->params['named']['show'])) {
					$txt = 'All Tasks';
					$toggle_url = str_replace('/show:unfinished', '', $this->here);
				} else {
					$txt = 'Unfinished Tasks';
					$toggle_url = ($this->here == '/') ? '/job_queues/index/show:unfinished' : $this->here . '/show:unfinished';
				}
				echo $this->Html->link($txt, $toggle_url); 
			?>
		</li>
		<li><?php echo $this->Html->link('Log',array('controller'=>'job_queues','action'=>'task_log'));?></li>
	</ul>
</div>

<div class="jobqueue index">
	<h2><?php echo __('Tasks'); ?></h2>
	<table cellpadding="0" cellspacing="0">
	<tr>
			<th><?php echo $this->Paginator->sort('id'); ?></th>
			<th><?php echo $this->Paginator->sort('queue'); ?></th>
			<th><?php echo $this->Paginator->sort('type'); ?></th>
			<th><?php echo $this->Paginator->sort('function'); ?></th>
			<th><?php echo $this->Paginator->sort('params'); ?></th>
			<!--<th><?php //echo $this->Paginator->sort('worker_id'); ?></th>-->
			<th><?php echo $this->Paginator->sort('retry'); ?></th>
			<!--<th><?php //echo $this->Paginator->sort('progress'); ?></th>-->
			<th><?php echo $this->Paginator->sort('status'); ?></th>
			<th><?php echo $this->Paginator->sort('description'); ?></th>
			<!--<th><?php //echo $this->Paginator->sort('failed'); ?></th>-->
			<th><?php echo $this->Paginator->sort('created'); ?></th>
			<th><?php echo $this->Paginator->sort('fetched'); ?></th>
			<th><?php echo $this->Paginator->sort('finished'); ?></th>
			<th class="actions"><?php echo __('Actions'); ?></th>
	</tr>
	<?php
	foreach ($jobQueues as $jobQueue): 
		$did_job_fail = ($jobQueue['JobQueue']['status'] == 'Failed') ? 'style="background-color:pink;"' : '';
	?>

	<tr <?php echo $did_job_fail; ?>>
		<td><?php echo h($jobQueue['JobQueue']['id']); ?>&nbsp;</td>
		<td><?php echo h($jobQueue['JobQueue']['queue']); ?>&nbsp;</td>
		<td><?php echo h($jobQueue['JobQueue']['type']); ?>&nbsp;</td>
		<td><?php echo h($jobQueue['JobQueue']['function']); ?>&nbsp;</td>
		<td><?php
			if(!empty($jobQueue['JobQueue']['params'])){
				$params = unserialize($jobQueue['JobQueue']['params']);
				foreach($params as $i =>$param){
					if (is_array($param)) {
						echo ($i+1).') Array </br>';
					} else {
						echo ($i+1).') '. h($param).' </br>';
					}
				}
			}
		?>&nbsp;</td>
		
		<!--<td><?php //echo h($jobQueue['JobQueue']['worker_id']); ?>&nbsp;</td>-->
		<td><?php echo h($jobQueue['JobQueue']['retry']); ?>&nbsp;</td>
		<!--<td><?php echo h($jobQueue['JobQueue']['progress']); ?>&nbsp;</td>-->
		<td><?php echo h($jobQueue['JobQueue']['status']); ?>&nbsp;</td>
		<td><?php echo h($jobQueue['JobQueue']['description']); ?>&nbsp;</td>
		<!--<td><?php echo h($jobQueue['JobQueue']['failed']); ?>&nbsp;</td>-->
		<td><?php echo h($jobQueue['JobQueue']['created']); ?>&nbsp;</td>
		<td><?php echo h($jobQueue['JobQueue']['fetched']); ?>&nbsp;</td>
		<td><?php echo h($jobQueue['JobQueue']['finished']); ?>&nbsp;</td>
		<td class="actions">
			<?php 
				echo $this->Html->link(__('Edit'), array('action' => 'edit', $jobQueue['JobQueue']['id']));
				$show = (isset($this->params['named']['show'])) ? '/1' : '';
				$extra = '/'.$this->params['paging']['JobQueue']['page'] . $show;
				echo $this->Html->link(__('Reset'), '/job_queues/reset/'.$jobQueue['JobQueue']['id'].$extra);
				echo $this->Form->postLink(
					__('Delete'), 
					array('action' => 'delete', $jobQueue['JobQueue']['id']), 
					array(), 
					__('Are you sure you want to delete # %s?', 
					$jobQueue['JobQueue']['id'])
				); 
			?>
		</td>
	</tr>
<?php endforeach; ?>
	</table>
	<p>
	<?php
	echo $this->Paginator->counter(array(
	'format' => __('Page {:page} of {:pages}, showing {:current} records out of {:count} total, starting on record {:start}, ending on {:end}')
	));
	?>	</p>

	<div class="paging">
	<?php
		echo $this->Paginator->prev('< ' . __('previous'), array(), null, array('class' => 'prev disabled'));
		echo $this->Paginator->numbers(array('separator' => ''));
		echo $this->Paginator->next(__('next') . ' >', array(), null, array('class' => 'next disabled'));
	?>
	</div>
</div>
<!--
<div class="actions">
	<h3><?php echo __('Actions'); ?></h3>
	<ul>
		<li>
			<?php 
				if(isset($this->params['named']['show'])) {
					$txt = 'All Tasks';
					$toggle_url = str_replace('/show:unfinished', '', $this->here);
				} else {
					$txt = 'Unfinished Tasks';
					$toggle_url = ($this->here == '/') ? '/job_queues/index/show:unfinished' : $this->here . '/show:unfinished';
				}
				
				echo $this->Html->link($txt, $toggle_url); 
			?>
		</li>
		<li><?php echo $this->Html->link('Workers',array('controller'=>'jobqueue_workers', 'action'=>'index'));?></li>
		<li><?php echo $this->Html->link('Worker Log',array('controller'=>'jobqueue_workers', 'action'=>'worker_log'));?></li>
	</ul>
</div>
-->
