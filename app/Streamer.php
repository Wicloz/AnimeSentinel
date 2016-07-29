<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Streamer extends Model
{
  public $incrementing = false;

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
  *
  * @return \Illuminate\Database\Eloquent\Relations\Relation
  */
  public function videos() {
    return $this->hasMany(Video::class);
  }

  /**
  * Get all shows from this streamer.
  *
  * @return \Illuminate\Database\Eloquent\Relations\Relation
  */
  public function streamers() {
    return $this->belongsToMany(Show::class, 'videos');
  }

  /**
  * Get the full url for this streamer's details page.
  *
  * @return string
  */
  public function getDetailsUrlAttribute() {
    return url('/streamers/'.$this->id);
  }
}
