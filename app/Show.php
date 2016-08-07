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
    'mal_id', 'thumbnail_id', 'title', 'alts', 'description', 'type', 'genres', 'episode_amount', 'episode_duration', 'airing_start', 'airing_end', 'hits',
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
   * The attributes that should be mutated to dates.
   *
   * @var array
   */
  protected $dates = ['airing_start', 'airing_end', 'cache_updated_at', 'created_at', 'updated_at'];

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
    return $this->belongsToMany(Streamer::class, 'videos')->distinct();
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
  * Get the show flag for this show.
  *
  * @return \Illuminate\Database\Eloquent\Relations\Relation
  */
  public function show_flag() {
    return $this->hasOne(ShowFlag::class, 'mal_id', 'mal_id');
  }

  /**
   * Include show's where the requested title matches any alt.
   *
   * @return \Illuminate\Database\Eloquent\Builder
   */
  public function scopeWithTitle($query, $title) {
    // fuzz title, allows matching of ' and ', ' to ' and '&' to each other
    $title = str_replace('&', '%', str_fuzz($title));
    // allow case insensitive matching of greek characters
    $title = preg_replace('/[α-ωΑ-Ω]/u', '\\u03__', $title);
    // encode to json, then escape all unescaped \'s
    $title = preg_replace('/([^\\\\])\\\\([^\\\\])/u', '$1\\\\\\\\$2', json_encode($title));
    return $query->where('alts', 'like', '%'.$title.'%');
  }

  /**
  * Get the latest subbed episode number for this show.
  *
  * @return integer
  */
  public function getLatestSubAttribute() {
    return $this->videos()
                ->where('translation_type', 'sub')
                ->orderBy('episode_num', 'desc')
                ->orderBy('uploadtime', 'asc')
                ->first();
  }

  /**
  * Get the latest dubbed episode number for this show.
  *
  * @return integer
  */
  public function getLatestDubAttribute() {
    return $this->videos()
                ->where('translation_type', 'dub')
                ->orderBy('episode_num', 'desc')
                ->orderBy('uploadtime', 'asc')
                ->first();
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
  * Get a list of episodes of the requested type.
  *
  * @return array
  */
  public function episodes($translation_type) {
    return $this->videos()
                ->distinct()
                ->where('translation_type', $translation_type)
                ->groupBy('episode_num')
                ->orderBy('episode_num', 'asc')
                ->get();
  }

  /**
  * Get the episode of the requested type and number.
  *
  * @return Video
  */
  public function episode($translation_type, $episode_num) {
    return $this->videos()
                ->episode($translation_type, $episode_num)
                ->first();
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
    if (isset($this->mal_id)) {
      return 'http://myanimelist.net/anime/'.$this->mal_id;
    } else {
      return null;
    }
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
    if ($this->cache_updated_at->diffInHours(Carbon::now()) >= 48) {
      ShowManager::updateShowCache($this->id);
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
