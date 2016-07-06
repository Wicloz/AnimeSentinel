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
    'streamer_id', 'show_id', 'translation_type', 'episode_num', 'uploadtime', 'link_stream', 'link_episode', 'hits', 'link_video', 'resolution',
  ];

  /**
  * Get the show this video belongs to.
  *
  * @return \Illuminate\Database\Eloquent\Relations\Relation
  */
  public function show() {
    return $this->belongsTo(Show::class);
  }

  /**
  * Get the streamer this video belongs to.
  *
  * @return \Illuminate\Database\Eloquent\Relations\Relation
  */
  public function streamer() {
    return $this->belongsTo(Streamer::class);
  }

  /**
   * Scope a query to only include video's for this episode.
   *
   * @return \Illuminate\Database\Eloquent\Builder
   */
  public function scopeEpisode($query, $translation_type, $episode_num) {
    return $query->where('translation_type', $translation_type)->where('episode_num', $episode_num);
  }

  /**
  * Get the full url for this videos stream page.
  *
  * @return string
  */
  public function getEpisodeUrlAttribute() {
    return url('/anime/'.$this->show_id.'/'.$this->translation_type.'/episode-'.$this->episode_num);
  }

  /**
  * Get the full url for the episode page related to this video.
  *
  * @return string
  */
  public function getStreamUrlAttribute() {
    return url('/anime/'.$this->show_id.'/'.$this->translation_type.'/episode-'.$this->episode_num.'/'.$this->id);
  }
}
