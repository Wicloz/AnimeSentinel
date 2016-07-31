<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\AnimeSentinel\VideoManager;
use Carbon\Carbon;

class Video extends Model
{
  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'streamer_id', 'show_id', 'translation_type', 'episode_num', 'uploadtime', 'link_stream', 'link_episode', 'notes', 'hits', 'link_video', 'resolution',
  ];

  /**
   * The attributes that should be mutated to dates.
   *
   * @var array
   */
  protected $dates = ['uploadtime', 'created_at', 'updated_at'];

  /**
   * Overwrite the save method to properly handle the compound key.
   */
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
   * Overwrite the find method to properly handle the compound key.
   */
  public static function find(array $data = []) {
    return Self::where('show_id', $data['show_id'])
               ->where('translation_type', $data['translation_type'])
               ->where('episode_num', $data['episode_num'])
               ->where('streamer_id', $data['streamer_id'])
               ->where('mirror', $data['mirror'])
               ->first();
  }

  /**
   * Return all video's which are 'the same'
   *
   * @return array
   */
  public static function sameVideo($video) {
    return Self::where('streamer_id', $video->streamer_id)
               ->where('show_id', $video->show_id)
               ->where('translation_type', $video->translation_type)
               ->where('episode_num', $video->episode_num)
               ->where('link_video', $video->link_video)
               ->where('resolution', $video->resolution)
               ->get();
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
  * Get a string that uniquely identifies this episode.
  *
  * @return string
  */
  public function getEpisodeIdAttribute() {
    if (isset($this->mal_id)) {
      return 'mal:'.$this->mal_id.',tt:'.$this->translation_type.',num:'.$this->episode_num;
    } else {
      return 'id:'.$this->show_id.',tt:'.$this->translation_type.',num:'.$this->episode_num;
    }
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
    return url('/anime/'.$this->show_id.'/'.$this->translation_type.'/episode-'.$this->episode_num.'/'.$this->streamer_id.'/'.$this->mirror);
  }

  /**
  * Get the full url for the episode page for the previous episode.
  *
  * @return string
  */
  public function getPreEpisodeUrlAttribute() {
    $episode = $this->show->episode($this->translation_type, $this->episode_num - 1);
    if (!empty($episode)) {
      $episode = $episode->episode_url;
    }
    return $episode;
  }

  /**
  * Get the full url for the episode page for the next episode.
  *
  * @return string
  */
  public function getNextEpisodeUrlAttribute() {
    $episode = $this->show->episode($this->translation_type, $this->episode_num + 1);
    if (!empty($episode)) {
      $episode = $episode->episode_url;
    }
    return $episode;
  }

  /**
  * Refresh the link_video for this video when it is needed.
  *
  * @return string
  */
  public function getLinkVideoAttribute($value) {
    // TODO: proper check
    if (isset($this->updated_at) && $this->updated_at->diffInHours(Carbon::now()) >= 24) {
      $value = VideoManager::findVideoLink($this);
      $this->updated_at = Carbon::now();
      $this->link_video = $value;
      $this->save();
    }
    return $value;
  }
}
