<?php
// TODO: See the commands section of documentation.  Some easy steps to
// create worker queues with a set number on the call

// TODO: Resets - Use php try catch. In the catch enqueue the job again.

Configure::write('CakeResque.Redis.host', 'localhost');
Configure::write('CakeResque.Worker.workers', 1);

// TODO: Is there an easy way to find out when a Job fails or does not complete?
Configure::write('CakeResque.Job.track', true);

// Configure writing logs to MongoDB or use the default to logs/resque.log
Configure::write('CakeResque.Log.handler','MongoDB');
Configure::write('CakeResque.Log.target','mongodb://localhost:27017,vms,jobqueue');


Configure::write('CakeResque.Queues.0.queue','file_mover');
Configure::write('CakeResque.Queues.1.queue','region');
Configure::write('CakeResque.Queues.2.queue','analytics');

Configure::write('CakeResque.Scheduler.enabled', true);
?>
