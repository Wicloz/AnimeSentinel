<?php

namespace App;

use App\Scopes\CacheShowScope;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use App\AnimeSentinel\ShowManager;

class Show extends Model
{
  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'mal_id', 'thumbnail_id', 'title', 'alts', 'description', 'type', 'genres', 'episode_amount', 'episode_duration', 'hits',
  ];

  /**
   * The attributes that should be casted to native types.
   *
   * @var array
   */
  protected $casts = [
    'alts' => 'array',
    'genres' => 'array',
  ];
  /**
   * Ensure both cache handling and decoding occurs.
   */
  public function getAltsAttribute($value) {
    $this->handleCaching();
    return json_decode($value);
  }
  public function getGenresAttribute($value) {
    $this->handleCaching();
    return json_decode($value);
  }

  /**
  * Get all videos related to this show.
  *
  * @return \Illuminate\Database\Eloquent\Relations\Relation
  */
  public function videos() {
    return $this->hasMany(Video::class);
  }

  /**
  * Get all streamers for this show.
  *
  * @return \Illuminate\Database\Eloquent\Relations\Relation
  */
  public function streamers() {
    return $this->belongsToMany(Streamer::class, 'videos');
  }

  /**
  * Get the mal flag for this show.
  *
  * @return \Illuminate\Database\Eloquent\Relations\Relation
  */
  public function mal_flag() {
    return $this->hasOne(MalFlag::class, 'mal_id', 'mal_id');
  }

  /**
   * Include show's where the requested title matches any alt.
   *
   * @return \Illuminate\Database\Eloquent\Builder
   */
  public function scopeWithTitle($query, $title) {
    return $query->where('alts', 'like', '%'.json_encode($title).'%');
  }

  /**
  * Get the latest subbed episode number for this show.
  *
  * @return integer
  */
  public function getLatestSubAttribute() {
    $episode_num = $this->videos()->where('translation_type', 'sub')->max('episode_num');
    return $episode_num ? $episode_num : null;
  }

  /**
  * Get the latest dubbed episode number for this show.
  *
  * @return integer
  */
  public function getLatestDubAttribute() {
    $episode_num = $this->videos()->where('translation_type', 'dub')->max('episode_num');
    return $episode_num ? $episode_num : null;
  }

  /**
  * Get the first uploaded video
  *
  * @return Video
  */
  public function getFirstVideoAttribute() {
    return $this->videos()->orderBy('uploadtime', 'asc')->first();
  }

  /**
  * Get the list of subbed episodes videos.
  *
  * @return array
  */
  public function getEpisodesSubAttribute() {
    $episodes = $this->videos()
                     ->distinct()
                     ->where('translation_type', 'sub')
                     ->groupBy('episode_num')
                     ->orderBy('episode_num', 'asc')
                     ->get();
    return $episodes;
  }

  /**
  * Get the list of dubbed episodes videos.
  *
  * @return array
  */
  public function getEpisodesDubAttribute() {
    $episodes = $this->videos()
                     ->distinct()
                     ->where('translation_type', 'dub')
                     ->groupBy('episode_num')
                     ->orderBy('episode_num', 'asc')
                     ->get();
    return $episodes;
  }

  /**
  * Get the full url for this show's details page.
  *
  * @return string
  */
  public function getDetailsUrlAttribute() {
    return url('/anime/'.$this->id);
  }

  /**
  * Get the url to this show's MAL page.
  *
  * @return string
  */
  public function getMalUrlAttribute() {
    return 'http://myanimelist.net/anime/'.$this->mal_id; // NOTE: MAL does not have https
  }

  /**
  * Get the url to this show's local thumbnail.
  *
  * @return string
  */
  public function getThumbnailUrlAttribute() {
    return url('/media/thumbnails/'.$this->thumbnail_id);
  }

  /**
  * Update this show's cached infomation when needed.
  */
  public function handleCaching() {
    // TODO: dynamic cache time
    if ($this->updated_at->diffInHours(Carbon::now()) >= 48) {
      $this->updated_at = Carbon::now();
      ShowManager::updateShowCache($this);
    }
  }

  /**
  * Handle caching calls.
  */
  public function getTitleAttribute($value) {
    $this->handleCaching();
    return $value;
  }
  public function getDescriptionAttribute($value) {
    $this->handleCaching();
    return $value;
  }
  public function getTypeAttribute($value) {
    $this->handleCaching();
    return $value;
  }
  public function getEpisodeAmountAttribute($value) {
    $this->handleCaching();
    return $value;
  }
  public function getEpisodeDurationAttribute($value) {
    $this->handleCaching();
    return $value;
  }
}
