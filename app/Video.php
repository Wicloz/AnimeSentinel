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
      'streamer_id', 'show_id', 'anime_type', 'episode_num', 'uploadtime', 'link', 'videolink', 'resolution',
  ];
}
