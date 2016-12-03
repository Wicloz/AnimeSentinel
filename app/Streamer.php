<?php

namespace App;

class Streamer extends BaseModel
{
  public $incrementing = false;

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'id', 'name', 'link_home', 'link_recent', 'enabled',
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
    return fullUrl('/streamers/'.$this->id);
  }
}
