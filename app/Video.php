<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Video extends Model
{
  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
      'streamer_id', 'show_id', 'translation_type', 'episode_num', 'uploadtime', 'link', 'videolink', 'resolution',
  ];

  /**
  * Get the show this video belongs to.
  */
  public function show() {
    return $this->belongsTo(Show::class);
  }

  /**
  * Get the streamer this video belongs to.
  */
  public function streamer() {
    return $this->belongsTo(Streamer::class);
  }
}
