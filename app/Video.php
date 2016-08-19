<?php

namespace App;

use App\AnimeSentinel\VideoManager;
use Carbon\Carbon;

class Video extends BaseModel
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
  protected $dates = ['uploadtime', 'cache_updated_at', 'created_at', 'updated_at'];

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
  public static function find($data) {
    if (is_array($data)) {
      return Self::where('show_id', $data['show_id'])
                 ->where('translation_type', $data['translation_type'])
                 ->where('episode_num', $data['episode_num'])
                 ->where('streamer_id', $data['streamer_id'])
                 ->where('mirror', $data['mirror'])
                 ->first();
    }
    else {
      return Self::where('id', $data)->first();
    }
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
    return Streamer::whereIn('id', $streamer_ids)->get();
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
  * Get the full url for this videos episode page.
  *
  * @return string
  */
  public function getEpisodeUrlAttribute() {
    return fullUrl('/anime/'.$this->show_id.'/'.slugify($this->show->title).'/'.$this->translation_type.'/episode-'.$this->episode_num);
  }

  /**
  * Get a static url for this videos episode page.
  *
  * @return string
  */
  public function getEpisodeUrlStaticAttribute() {
    return staticUrl($this->show, $this);
  }

  /**
  * Get the surface area of the video.
  *
  * @return integer
  */
  public function getVideoSurfaceAttribute() {
    $resolutions = explode('x', $this->resolution);
    return $resolutions[0] * $resolutions[1];
  }

  /**
  * Get the aspect ratio of the video.
  *
  * @return integer
  */
  public function getVideoAspectAttribute() {
    $resolutions = explode('x', $this->resolution);
    return $resolutions[1] / $resolutions[0];
  }

  /**
  * Return the 'best' out of all mirrors for this episode.
  *
  * @return string
  */
  public function getBestMirrorAttribute() {
    $mirrors = Self::where('show_id', $this->show_id)->episode($this->translation_type, $this->episode_num)->get();
    $bestMirror = null;

    foreach ($mirrors as $mirror) {
      if (playerSupport($mirror->link_video) && ($bestMirror === null || $mirror->video_surface > $bestMirror->video_surface)) {
        $bestMirror = $mirror;
      }
    }
    if ($bestMirror === null) {
      foreach ($mirrors as $mirror) {
        if ($bestMirror === null || $mirror->video_surface > $bestMirror->video_surface) {
          $bestMirror = $mirror;
        }
      }
    }

    return $bestMirror;
  }

  /**
  * Get the full url for the episode page with this mirror selected.
  *
  * @return string
  */
  public function getStreamUrlAttribute() {
    return fullUrl('/anime/'.$this->show_id.'/'.slugify($this->show->title).'/'.$this->translation_type.'/episode-'.$this->episode_num.'/'.$this->streamer_id.'/'.$this->mirror);
  }

  /**
  * Get the full url for the episode page for the previous episode.
  *
  * @return string
  */
  public function getPreEpisodeUrlAttribute() {
    $episode = Self::where('show_id', $this->show_id)->episode($this->translation_type, $this->episode_num - 1)->first();
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
    $episode = Self::where('show_id', $this->show_id)->episode($this->translation_type, $this->episode_num + 1)->first();
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
  public function getLinkVideoUpdatedAttribute() {
    // TODO: proper check
    if ($this->cache_updated_at->diffInHours(Carbon::now()) >= 2) {
      $this->link_video = VideoManager::findVideoLink($this);
      $this->cache_updated_at = Carbon::now();
      $this->save();
    }
    return $this->link_video;
  }
}
