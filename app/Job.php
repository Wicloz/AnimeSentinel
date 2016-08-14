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
    $job->available_at = Carbon::now()->addSeconds($attributes['available_at'] - $attributes['created_at']);
    $job->save();
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
}
