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
   * The attributes that should be mutated to dates.
   *
   * @var array
   */
  protected $dates = ['uploadtime', 'created_at', 'updated_at'];

  public function save(array $options = []) {
    if (empty($this->mirror)) {
      $max_mirror = Self::where('streamer_id', $this->streamer_id)
                        ->where('show_id', $this->show_id)
                        ->where('translation_type', $this->translation_type)
                        ->where('episode_num', $this->episode_num)
                        ->max('mirror');
      $this->mirror = $max_mirror + 1;
    }
    parent::save($options);
  }

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
   * Scope a query to only include video's which are 'the same'.
   *
   * @return \Illuminate\Database\Eloquent\Builder
   */
  public function scopeSameVideo($query, $video) {
    return $query->where('streamer_id', $video->streamer_id)
                 ->where('show_id', $video->show_id)
                 ->where('translation_type', $video->translation_type)
                 ->where('episode_num', $video->episode_num);
  }

  /**
  * Get all streamers that stream this episode.
  *
  * @return array
  */
  public function getStreamersAttribute() {
    $streamer_ids = Self::where('show_id', $this->show_id)->episode($this->translation_type, $this->episode_num)
                       ->distinct()->pluck('streamer_id');
    foreach ($streamer_ids as $streamer_id) {
      $streamers[] = Streamer::find($streamer_id);
    }
    return $streamers;
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
    return url('/anime/'.$this->show_id.'/'.$this->translation_type.'/episode-'.$this->episode_num.'/'.$this->mirror);
  }
}
