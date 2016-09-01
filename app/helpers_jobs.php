<?php

function queueJob($job, $queue = 'default', $connection = 'database') {
  // Prepare job data
  $job->db_data['connection'] = $connection;

  if ($job->db_data['show_id'] !== null) {
    // Check whether a higher job is queued
    $highers = \App\Job::higherThan($job->db_data['job_task'], $job->db_data['show_id']);
    if (count($highers) > 0) {
      foreach ($highers as $higher) {
        $higher->elevateQueue($queue);
      }
      return;
    }

    // Check whether the same job is already queued
    $duplicates = \App\Job::where([
      ['job_task', '=', $job->db_data['job_task']],
      ['show_id', '=', $job->db_data['show_id']],
      ['job_data', '=', json_encode($job->db_data['job_data'])],
      ['reserved_at', '=', null],
    ])->get();
    if (count($duplicates) > 0) {
      foreach ($duplicates as $duplicate) {
        $duplicate->elevateQueue($queue);
      }
      return;
    }

    // Remove any lower jobs
    $newQueue = \App\Job::deleteLowerThan($job->db_data['job_task'], $job->db_data['show_id']);
    if (in_array($newQueue, array_get_parents(config('queue.queuehierarchy'), $queue))) {
      $queue = $newQueue;
    }
  }

  // Add this job to the queue
  if ($connection === 'database')  {
    $job_id = dispatch($job->onQueue($queue));
    $job = \App\Job::find($job_id);
    $job->job_task = $job->db_data['job_task'];
    $job->show_id = $job->db_data['show_id'];
    $job->job_data = $job->db_data['job_data'];
    $job->save();
  } else {
    dispatch($job->onConnection($connection));
  }
}

function preHandleJob($job_dbdata) {
  // Set job query data
  $job_queryData = [
    ['job_task', '=', $job_dbdata['job_task']],
    ['show_id', '=', $job_dbdata['show_id']],
    ['job_data', '=', json_encode($job_dbdata['job_data'])],
  ];
  // Remove any inferior queued jobs
  \App\Job::deleteLowerThan($job_dbdata['job_task'], $job_dbdata['show_id']);
  // If this is queued as a job, remove it from the queue
  \App\Job::where(array_merge($job_queryData, [['reserved_at', '=', null]]))->delete();
  // Hovever, if that job is in progress, wait for it to complete instead of running this function,
  // but only if this function isn't started from the job in progress
  if ($job_dbdata['connection'] === 'sync' && count(\App\Job::where(array_merge($job_queryData, [['reserved_at', '!=', null]]))->get()) > 0) {
    while (count(\App\Job::where(array_merge($job_queryData, [['reserved_at', '!=', null]]))->get()) > 0) {
      sleep(1);
    }
    return false;
  }
  return true;
}
