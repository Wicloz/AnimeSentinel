<?php

namespace App;

use App\Scopes\CacheShowScope;
use Carbon\Carbon;
use App\AnimeSentinel\Actions\ShowManager;

class Show extends BaseModel
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
    return (array) json_decode($value);
  }
  public function getGenresAttribute($value) {
    $this->handleCaching();
    return (array) json_decode($value);
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
  public function scopeWithTitle($query, $title, $allowPartial = false) {
    // fuzz title
    $title = str_fuzz($title, false);
    // allow matching of ' and ', ' to ' and '&' to each other
    // allow matching of ': ' to ' '
    $title = str_replace(': ', '% ', str_replace('&', '%', $title));
    // allow case insensitive matching of greek characters
    $title = preg_replace('/[α-ωΑ-Ω]/u', '\\u03__', $title);
    // encode to json, then escape all unescaped \'s
    $title = preg_replace('/([^\\\\])\\\\([^\\\\])/u', '$1\\\\\\\\$2', json_encode($title));
    // return final query
    if (!$allowPartial) {
      return $query->whereLike('alts', '%'.$title.'%');
    } else {
      return $query->whereLike('alts', '%"%'.str_replace_last('"', '', str_replace_first('"', '', $title)).'%"%');
    }
  }

  /**
   * Searches for shows matching the requested query, ordered by relevance.
   *
   * @return array
   */
  public static function search($query, $limit, $fill = true) {
    $results = [];

    // first match with full titles
    $results = Self::orderBy('hits', 'desc')->take($limit)->withTitle($query)->get();
    // match with partial titles
    $results = $results->merge(Self::orderBy('hits', 'desc')->whereNotIn('id', $results->pluck('id'))->take($limit - count($results))->withTitle($query, true)->get()->all());

    // match with partial titles, with non-alphanumeric characters ignored
    $thisQuery = str_to_url($query, '%', '/[^a-zA-Z0-9]/u');
    if (strlen(str_replace('%', '', $thisQuery)) >= 3) {
      $results = $results->merge(Self::orderBy('hits', 'desc')->whereNotIn('id', $results->pluck('id'))->take($limit - count($results))->withTitle($thisQuery, true)->get()->all());
    }
    // match with partial titles, with non-alphabetic characters ignored
    $thisQuery = str_to_url($query, '%', '/[^a-zA-Z]/u');
    if (strlen(str_replace('%', '', $thisQuery)) >= 3) {
      $results = $results->merge(Self::orderBy('hits', 'desc')->whereNotIn('id', $results->pluck('id'))->take($limit - count($results))->withTitle($thisQuery, true)->get()->all());
    }

    if ($fill) {
      // match any titles with the same letters in the same order, at any location
      $thisQuery = str_to_url($query, '%', '/[^a-zA-Z]/u');
      if (strlen(str_replace('%', '', $thisQuery)) >= 3) {
        $thisQuery = '%'.str_to_url($thisQuery, '$1%', '/([a-zA-Z])/u');
        $results = $results->merge(Self::orderBy('hits', 'desc')->whereNotIn('id', $results->pluck('id'))->take($limit - count($results))->withTitle($thisQuery)->get()->all());
      }
    }

    return $results;
  }

  /**
   * Find the show with the given URL data.
   * Return null if no show was found.
   *
   * @return \App\Show
   */
  public static function getShowFromUrl($show_id, $title) {
    if (is_numeric($show_id)) {
      $show = Show::find($show_id);
    }

    if (isset($show)) {
      if ($title === slugify($show->title) || empty($title)) {
        return $show;
      }
      else {
        foreach ($show->alts as $alt) {
          if ($title === slugify($alt)) {
            return $show;
          }
        }
      }
    }

    if (!empty($title)) {
      $replace = [
        '⧸' => '/',
        '⧹' => '\\',
      ];
      foreach ($replace as $from => $to) {
        $title = str_replace($from, $to, $title);
      }
      $title = str_replace('‑', ' ', $title);
      $show = Show::withTitle($title)->first();
      if (isset($show)) {
        return $show;
      } else {
        $show = ShowManager::addShowWithTitle($title, false, 'default');
        return $show;
      }
    }

    return null;
  }

  /**
  * Get whether the show is currently airing for the requested translation type.
  *
  * @return boolean
  */
  public function isAiring($translation_type) {
    $latest = 'latest_'.$translation_type;
    return !isset($this->episode_amount) || ($this->$latest !== null && $this->$latest->episode_num < $this->episode_amount);
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
  * Get the last uploaded video
  *
  * @return Video
  */
  public function getLastVideoAttribute() {
    return $this->videos()->orderBy('uploadtime', 'desc')->first();
  }


  /**
  * Get a list of episodes of the requested type.
  *
  * @return array
  */
  public function episodes($translation_type) {
    return $this->videos()
                ->where('translation_type', $translation_type)
                ->distinctOn('episode_num')
                ->orderBy('episode_num', 'desc')
                ->orderBy('uploadtime', 'asc')
                ->get();
  }

  /**
  * Get the full url for this show's details page.
  *
  * @return string
  */
  public function getDetailsUrlAttribute() {
    return fullUrl('/anime/'.$this->id.'/'.slugify($this->title));
  }

  /**
  * Get a static url for this show's details page.
  *
  * @return string
  */
  public function getDetailsUrlStaticAttribute() {
    return fullUrl('/anime/-/'.slugify($this->title), true);
  }

  /**
  * Get the url to this show's MAL page.
  *
  * @return string
  */
  public function getMalUrlAttribute() {
    if (isset($this->mal_id)) {
      return 'https://myanimelist.net/anime/'.$this->mal_id;
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
    return fullUrl('/media/thumbnails/'.$this->thumbnail_id);
  }

  /**
  * Update this show's cached infomation when needed.
  */
  public function handleCaching() {
    // TODO: smarter cache time
    if ($this->cache_updated_at->diffInHours(Carbon::now()) >= rand(168, 336)) {
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
