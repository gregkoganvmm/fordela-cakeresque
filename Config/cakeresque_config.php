<?php
// Documentation - see http://cakeresque.kamisama.me/usage

// TODO: Resets - Use php try catch. In the catch enqueue the job again.

Configure::write('CakeResque.Redis.host', 'localhost');
Configure::write('CakeResque.Worker.workers', 1); // default number of workers if param not passed

// TODO: Is there an easy way to find out when a Job fails or does not complete?
Configure::write('CakeResque.Job.track', true);

// Configure writing logs to MongoDB or use the default to logs/resque.log
Configure::write('CakeResque.Log.handler','MongoDB');
Configure::write('CakeResque.Log.target',MONGO_DATABASE);

// Commands to run to get started (creates queue:workers default:10, region:10, file_mover:5)
// Console/cake CakeResque.CakeResque start --queue default --workers 10
// Console/cake CakeResque.CakeResque start --queue region --workers 10
// Console/cake CakeResque.CakeResque start --queue file_mover --workers 5

// Stop all workers
// Console/cake CakeResque.CakeResque stop --all

// General info about workers
// Console/cake CakeResque.CakeResque stats

Configure::write('CakeResque.Scheduler.enabled', true);
?>
