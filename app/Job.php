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
   */
  public static function higherThan($job_task, $show_title) {
    return Self::where('show_title', $show_title)->whereIn('job_task', array_get_parents(config('queue.jobhierarchy'), $job_task))->get();
  }

  /**
   * Returns all 'higher' jobs than the requested task, operating on the same show title.
   */
  public static function lowerThan($job_task, $show_title) {
    return Self::where('show_title', $show_title)->whereIn('job_task', array_get_childs(config('queue.jobhierarchy'), $job_task))->get();
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
