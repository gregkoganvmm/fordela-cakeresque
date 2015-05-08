<?php

// TODO: See the commands section of documentation.  Some easy steps to
// create worker queues with a set number on the call


// TODO: Explore VMS functon queue in AppController.php.  Where do we find the
// JobID?
// Not really necessary

// TODO: Goal, to not overwrite anything in the plugin but still have a way to
// track what has failed.
// Can be done as it previously was done by saving the status to update the
// JobQueue record


// TODO: Resets - Possible solution is to use CakeResque::enqueueIn after certain
// job types get enqueued.  The delayed job can check on the original job.

Configure::write('CakeResque.Redis.host', 'localhost');
Configure::write('CakeResque.Worker.workers', 1);

// TODO: Is there an easy way to find out when a Job fails or does not complete?
Configure::write('CakeResque.Job.track', true);

// TODO: How to configure writing logs to MongoDB or anywhere else?
//Configure::write('CakeResque.Log.handler','MongoDB');
//Configure::write('CakeResque.Log.target','mongodb://localhost:27017,vms,jobqueue');
Configure::write('CakeResque.Log.target',TMP.'logs'.DS.'tasks.log');

Configure::write('CakeResque.Queues.0.queue','file_mover');
Configure::write('CakeResque.Queues.1.queue','region');
Configure::write('CakeResque.Queues.2.queue','analytics');

Configure::write('CakeResque.Scheduler.enabled', true);
?>
