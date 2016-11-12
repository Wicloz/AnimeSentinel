<?php

namespace App;

use Carbon\Carbon;

class Job extends BaseModel
{
  public $timestamps = false;

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'job_task', 'show_id', 'job_data', 'queue', 'payload', 'attempts',
  ];

  /**
   * The attributes that should be mutated to dates.
   *
   * @var array
   */
  protected $dates = ['reserved_at', 'available_at', 'created_at'];

  /**
   * The storage format of the model's date columns.
   *
   * @var string
   */
  protected $dateFormat = 'U';

  /**
   * The attributes that should be casted to native types.
   *
   * @var array
   */
  protected $casts = [
    'job_data' => 'collection',
  ];

  /**
   * Override the create function to properly set timestamps.
   */
  public static function create(array $attributes = []) {
    $job = parent::create($attributes);
    $job->created_at = Carbon::now();
    if (isset($attributes['available_at']) && isset($attributes['created_at'])) {
      $job->available_at = Carbon::now()->addSeconds($attributes['available_at'] - $attributes['created_at']);
    } else {
      $job->available_at = Carbon::now();
    }
    $job->save();
  }

  /**
   * Overwrite the find method to properly handle the compound key.
   */
  public static function find($data) {
    if (is_array($data)) {
      return Self::where('job_task', $data['job_task'])
                 ->where('show_id', $data['show_id'])
                 ->where('job_data', $data['job_data'])
                 ->first();
    }
    else {
      return Self::where('id', $data)->first();
    }
  }

  /**
  * Encode and decode the job_data to/from JSON.
  */
  public function getJobDataAttribute($value) {
    return json_decode($value);
  }
  public function setJobDataAttribute($value) {
    $this->attributes['job_data'] = json_encode($value);
  }

  /**
   * Returns all 'higher' jobs than the requested task, operating on the same show title.
   */
  public static function higherThan($job_task, $show_id, $unReserved = true) {
    $query = Self::where('show_id', $show_id)->whereIn('job_task', array_get_parents(config('queue.jobhierarchy'), $job_task));
    if ($unReserved) {
      $query->whereNull('reserved_at');
    }
    return $query->get();
  }

  /**
   * Returns all 'lower' jobs than the requested task, operating on the same show title.
   */
  public static function lowerThan($job_task, $show_id, $unReserved = true) {
    $query = Self::where('show_id', $show_id)->whereIn('job_task', array_get_childs(config('queue.jobhierarchy'), $job_task));
    if ($unReserved) {
      $query->whereNull('reserved_at');
    }
    return $query->get();
  }

  /**
   * Deletes all jobs 'lower' than or equal to the requested task, operating on the same show title.
   * Ignores reserved jobs.
   *
   * @return string
   */
  public static function deleteLowerThan($job_task, $show_id = null, & $highestQueue = null, & $shortestDelay = null) {
    $lower_tasks = array_get_childs(config('queue.jobhierarchy'), $job_task);
    if ($highestQueue !== null || $shortestDelay !== null) {
      $lower_jobs = Self::whereIn('job_task', $lower_tasks)
                        ->where('show_id', $show_id)
                        ->whereNull('reserved_at')
                        ->get();
    }

    // Determine the 'highest' queue of all jobs that will be removed
    if ($highestQueue !== null) {
      foreach ($lower_jobs->pluck('queue') as $queue) {
        if (in_array($queue, array_get_parents(config('queue.queuehierarchy'), $highestQueue))) {
          $highestQueue = $queue;
        }
      }
    }

    // Determine the 'shortest' delay of all jobs that will be removed
    if ($shortestDelay !== null) {
      foreach ($lower_jobs->pluck('available_at') as $delay) {
        if ($delay->lt($shortestDelay)) {
          $shortestDelay = $delay;
        }
      }
    }

    // Remove all lower jobs
    Self::whereIn('job_task', $lower_tasks)
        ->where('show_id', $show_id)
        ->whereNull('reserved_at')
        ->delete();
  }

  /**
   * Elevates this job's queue if the new one is higher in the hierarchy.
   */
  public function elevateQueue($newQueue) {
    if (in_array($newQueue, array_get_parents(config('queue.queuehierarchy'), $this->queue))) {
      $this->queue = $newQueue;
      $this->save();
    }
  }

  /**
   * Change the available_at time of this job if the new time comes earlier.
   */
  public function elevateDelay($newDelay) {
    if ($newDelay === null) {
      $newDelay = Carbon::now();
    }
    if ($newDelay->lt($this->available_at)) {
      $this->available_at = $newDelay;
      $this->save();
    }
  }
}
