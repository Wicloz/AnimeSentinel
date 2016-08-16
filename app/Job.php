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
    'job_task', 'show_title', 'job_data', 'queue', 'payload', 'attempts',
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
                 ->where('show_title', $data['show_title'])
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
   * Ignore reserved jobs.
   */
  public static function higherThan($job_task, $show_title) {
    return Self::where('show_title', $show_title)->where('reserved', 0)
               ->whereIn('job_task', array_get_parents(config('queue.jobhierarchy'), $job_task))->get();
  }

  /**
   * Returns all 'lower' jobs than the requested task, operating on the same show title.
   * Ignore reserved jobs.
   */
  public static function lowerThan($job_task, $show_title) {
    return Self::where('show_title', $show_title)->where('reserved', 0)
               ->whereIn('job_task', array_get_childs(config('queue.jobhierarchy'), $job_task))->get();
  }

  /**
   * Deletes all jobs 'lower' than or equal to the requested task, operating on the same show title.
   * Doesn't ignore reserved jobs.
   *
   * @return string
   */
  public static function terminateLowerEqual($job_task, $show_title = null, $job_data = null) {
    $lower_tasks = array_get_childs(config('queue.jobhierarchy'), $job_task);

    // Determine the 'highest' queue of all jobs that will be removed
    $queues = Self::where(function ($query) use ($job_task, $show_title, $job_data) {
                      $query->where('job_task', $job_task)
                            ->where('show_title', $show_title)
                            ->where('job_data', json_encode($job_data));
                    })
                  ->orWhere(function ($query) use ($lower_tasks, $show_title) {
                      $query->whereIn('job_task', $lower_tasks)
                            ->where('show_title', $show_title);
                    })
                  ->get()->pluck('queue');
    $highestQueue = 'default';
    if (count($queues) > 0) {
      foreach ($queues as $queue) {
        if (in_array($queue, array_get_parents(config('queue.queuehierarchy'), $highestQueue))) {
          $highestQueue = $queue;
        }
      }
    }

    // Remove all applicable jobs
    Self::where(function ($query) use ($job_task, $show_title, $job_data) {
            $query->where('job_task', $job_task)
                  ->where('show_title', $show_title)
                  ->where('job_data', json_encode($job_data));
          })
        ->orWhere(function ($query) use ($lower_tasks, $show_title) {
            $query->whereIn('job_task', $lower_tasks)
                  ->where('show_title', $show_title);
          })
        ->delete();

    // TODO: Terminate the running job

    return $highestQueue;
  }

  /**
   * Elevates this job's queue if the new one is higher in the hierarchy.
   */
  public function elevateQueue($newQueue) {
    if (in_array($this->queue, array_get_childs(config('queue.queuehierarchy'), $newQueue))) {
      $this->queue = $newQueue;
      $this->delete();
      \App\Job::create($this->toArray());
    }
  }
}
