<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Streamer extends Model
{
  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
      'id', 'name', 'popularity', 'link_home', 'link_recent',
  ];

  /**
  * Get all videos from this streamer.
  */
  public function videos() {
    return $this->hasMany(Video::class);
  }

  /**
  * Get all shows from this streamer.
  */
  public function streamers() {
    return $this->belongsToMany(Show::class, 'videos');
  }
}
