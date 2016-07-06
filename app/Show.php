<?php

namespace App;

use App\Scopes\CacheShowScope;
use Illuminate\Database\Eloquent\Model;

class Show extends Model
{
  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
      'mal_id', 'name', 'alts', 'description', 'show_type',
  ];

  /**
  * Get all videos related to this show.
  */
  public function videos() {
    return $this->hasMany(Video::class);
  }

  /**
  * Get all streamers for this show.
  */
  public function streamers() {
    return $this->belongsToMany(Streamer::class, 'videos');
  }
}
