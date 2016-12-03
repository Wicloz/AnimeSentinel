<?php

namespace App;

use App\Scopes\CacheShowScope;
use Carbon\Carbon;
use App\AnimeSentinel\Actions\ShowManager;
use Illuminate\Support\Facades\Auth;
use App\AnimeSentinel\Downloaders;

class Show extends BaseModel
{
  private $malShow = null;

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'mal_id', 'remote_thumbnail_urls', 'local_thumbnail_ids', 'title', 'alts', 'description', 'prequels', 'sequels', 'summaries', 'specials', 'alternatives', 'others', 'type', 'genres', 'season', 'rating', 'episode_amount', 'episode_duration', 'airing_start', 'airing_end', 'airing_time', 'airing_type',
  ];

  /**
   * The attributes that should be casted to native types.
   *
   * @var array
   */
  protected $casts = [
    'remote_thumbnail_urls' => 'collection',
    'local_thumbnail_ids' => 'collection',
    'prequels' => 'collection',
    'sequels' => 'collection',
    'summaries' => 'collection',
    'specials' => 'collection',
    'alternatives' => 'collection',
    'others' => 'collection',
    'alts' => 'collection',
    'genres' => 'collection',
  ];

  /**
   * The attributes that should be mutated to dates.
   *
   * @var array
   */
  protected $dates = ['airing_start', 'airing_end', 'airing_time', 'cache_updated_at', 'created_at', 'updated_at'];
  public function getAiringTimeAttribute($value) {
    if ($value !== null) {
      return Carbon::createFromFormat('H:i:s', $value);
    }
    return $value;
  }
  public function getAiringStartAttribute($value) {
    if ($value !== null) {
      $value = Carbon::createFromFormat('Y-m-d', str_get_between($value, '', ' ') ? str_get_between($value, '', ' ') : $value);
      if (isset($this->airing_time)) {
        $value->setTime($this->airing_time->hour, $this->airing_time->minute, $this->airing_time->second);
      } else {
        $value->setTime(0, 0, 0);
      }
    }
    return $value;
  }
  public function getAiringEndAttribute($value) {
    if ($value !== null) {
      $value = Carbon::createFromFormat('Y-m-d', str_get_between($value, '', ' ') ? str_get_between($value, '', ' ') : $value);
      if (isset($this->airing_time)) {
        $value->setTime($this->airing_time->hour, $this->airing_time->minute, $this->airing_time->second);
      } else {
        $value->setTime(0, 0, 0);
      }
    }
    return $value;
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
  * Get the calculated airing type for this show and the requested translation type.
  *
  * @return string
  */
  public function expandedAiringType($translation_type) {
    if (isset($this->airing_type)) {
      return $this->airing_type;
    } elseif ($this->episodeDelayEstimate($translation_type) === 7) {
      return 'weekly';
    }
    return null;
  }

  /**
  * Get the calculated airing type for this show without taking the episode delay into account.
  *
  * @return string
  */
  protected function getAiringTypeAttribute() {
    if (isset($this->attributes['airing_type'])) {
      return $this->attributes['airing_type'];
    } elseif ($this->episode_amount === 1 || (isset($this->airing_start) && isset($this->airing_end) && $this->airing_start->eq($this->airing_end))) {
      return 'once';
    }
    return null;
  }

  /**
  * Get the mal data for this show for the logged in user.
  *
  * @return \Illuminate\Database\Eloquent\Collection
  */
  public function getMalShowAttribute() {
    if (Auth::check() && $this->malShow !== 'NA') {
      if ($this->malShow === null) {
        $malField = Auth::user()->malFields()->where('mal_id', $this->mal_id)->first();
        if (isset($malField)) {
          $this->malShow = $malField->mal_show;
        } else {
          $this->malShow = 'NA';
          return null;
        }
      }
      return $this->malShow;
    }
    return null;
  }

  /**
  * Set the cached mal show.
  */
  public function setMalShowAttribute($value) {
    $this->malShow = $value;
  }

  /**
  * Functions to print information in a fancy way.
  */
  public function printAlts() {
    return implode(', ', $this->alts->all());
  }
  public function printType() {
    return isset($this->type) ? ucwords($this->type) : 'Unknown';
  }
  public function printGenres() {
    if (count($this->genres) > 0) {
      return ucwords(implode(', ', $this->genres->all()));
    }
    else {
      return 'Unknown';
    }
  }
  public function printTotalEpisodes() {
    return isset($this->episode_amount) ? $this->episode_amount : 'Unknown';
  }
  public function printExpectedDuration($withEp = true) {
    if (isset($this->episode_duration)) {
      return fancyDuration($this->episode_duration * 60, false) . ($withEp ? ' per ep.' : '');
    }
    elseif (empty($this->mal) && $this->videos()->where('episode_num', '>=', 0)->avg('duration') !== null) {
      return fancyDuration($this->videos()->where('episode_num', '>=', 0)->avg('duration'), false) . ($withEp ? ' per ep.' : '');
    }
    else {
      return 'Unknown';
    }
  }
  public function printAvarageDuration($withEp = true) {
    if (empty($this->mal) && $this->videos()->where('episode_num', '>=', 0)->avg('duration') !== null) {
      return fancyDuration($this->videos()->where('episode_num', '>=', 0)->avg('duration')) . ($withEp ? ' per ep.' : '');
    }
    else {
      return 'NA';
    }
  }
  public function printExpectedAiring() {
    if (empty($this->airing_start) && empty($this->airing_end)) {
      return 'Unknown';
    }
    else {
      if (!empty($this->airing_start)) {
        if ($this->airing_start->hour !== 0 || $this->airing_start->minute !== 0 || $this->airing_start->second !== 0) {
          $string = $this->airing_start->format('M jS, o, H:i');
        } else {
          $string = $this->airing_start->format('M jS, o');
        }
      } else {
        $string = '?';
      }
      $string .= ' to ';
      if (!empty($this->airing_end)) {
        if ($this->airing_end->hour !== 0 || $this->airing_end->minute !== 0 || $this->airing_end->second !== 0) {
          $string .= $this->airing_end->format('M jS, o, H:i');
        } else {
          $string .= $this->airing_end->format('M jS, o');
        }
      } else {
        $string .= '?';
      }
      return $string;
    }
  }
  public function printSeason() {
    return isset($this->season) ? ucwords($this->season) : 'Unknown';
  }
  public function printRating() {
    return isset($this->rating) ? $this->rating : 'Unknown';
  }
  public function printStatus($translation_type) {
    if (empty($this->mal)) {
      if ($this->isAiring($translation_type)) {
        return 'Currently Airing';
      }
      elseif ($this->finishedAiring($translation_type)) {
        return 'Completed';
      }
      else {
        return 'Upcoming';
      }
    }
    elseif ($translation_type === 'sub') {
      if (!isset($this->airing_start) || Carbon::now()->endOfDay()->lt($this->airing_start)) {
        return 'Upcoming';
      }
      elseif (!isset($this->airing_end) || Carbon::now()->startOfDay()->lte($this->airing_end)) {
        return 'Currently Airing';
      }
      else {
        return 'Completed';
      }
    }
    else {
      return 'Unknown';
    }
  }
  public function printLatest($translation_type) {
    $latest = 'latest_'.$translation_type;
    if (empty($this->mal)) {
      if (!isset($this->$latest)) {
        if (!$this->videos_initialised) {
          return 'Searching for Episodes ...';
        }
        else {
          return 'No Episodes Available';
        }
      }
      else {
        return 'Episode ' . $this->$latest->episode_num;
      }
    }
    else {
      return 'Unknown';
    }
  }
  public function printNextUpload($translation_type, $dateFormat = 'l j F, Y') {
    if ($this->finishedAiring($translation_type)) {
      return 'NA';
    }

    elseif (!empty($this->mal) || $this->nextUploadEstimate($translation_type) === null) {
      return 'Unknown';
    }

    else {
      $date = $this->nextUploadEstimate($translation_type);
      $dateString = $date->format($dateFormat);

      if (Carbon::now()->gt($date->copy()->endOfDay())) {
        $dateString = '<span class="text-danger">' . $dateString . '</span>';
      }

      if ($date->hour !== 0 || $date->minute !== 0 || $date->second !== 0) {
        $timeString = $date->format('H:i');
        if (Carbon::now()->gt($date)) {
          $timeString = '<span class="text-danger">' . $timeString . '</span>';
        }
        $dateString .= ' at ' . $timeString;
      }

      return $dateString;
    }
  }
  public function printBroadcasts() {
    if ($this->expandedAiringType('sub') === 'irregular') {
      return 'No regular schedule';
    }
    elseif ($this->expandedAiringType('sub') === 'once') {
      return 'NA';
    }
    elseif ($this->expandedAiringType('sub') === 'weekly' && $this->broadcastDayEstimate('sub') !== null) {
      $broadcast = ucwords($this->broadcastDayEstimate('sub')).'s';
      if ($this->latest_sub !== null && ($this->latest_sub->uploadtime->hour !== 0 || $this->latest_sub->uploadtime->minute !== 0 || $this->latest_sub->uploadtime->second !== 0)) {
        $broadcast .= ' at '.$this->latest_sub->uploadtime->format('H:i');
      } elseif ($this->airing_time !== null) {
        $broadcast .= ' at '.$this->airing_time->format('H:i');
      }
      return $broadcast;
    }
    elseif ($this->episodeDelayEstimate('sub') !== null) {
      return 'Every '.$this->episodeDelayEstimate('sub').' days';
    }
    return 'Unknown';
  }

  /**
   * Include show's where the requested title matches any alt.
   *
   * @return \Illuminate\Database\Eloquent\Builder
   */
  public function scopeWithTitle($query, $title, $allowPartial = false) {
    // fuzz title
    $title = str_fuzz($title, false);
    // allow matching of ' and ', ' to ', ' und ' and '&' to each other
    // allow matching of ': ' to ' '
    $title = str_replace(': ', '% ', str_replace('&', '%', $title));
    // allow case insensitive matching of greek characters
    $title = preg_replace('/[α-ωΑ-Ω]/u', '\\u03__', $title);
    // encode to json, then escape all unescaped \'s
    $title = preg_replace('/([^\\\\])\\\\([^\\\\])/u', '$1\\\\\\\\$2', json_encode($title));
    // create final query title
    if ($allowPartial) {
      $title = '%"%'.str_replace_last('"', '', str_replace_first('"', '', $title)).'%"%';
    } else {
      $title = '%'.$title.'%';
    }
    // return query
    return $query->whereLike('alts', $title);
  }

  /**
   * Searches for shows matching the requested query, ordered by relevance.
   *
   * @return array
   */
  public static function search($search, $types, $genres, $ratings, $start = 0, $amount = null, $fill = false) {
    $results = [];
    $query = Self::skip($start)->take($amount)->orderBy('title');

    // searching by types
    $query->where(function ($query) use ($types) {
      $query->whereIn('type', $types);
      if ($types->contains('unknown')) {
        $query->orWhere('type', null);
      }
    });

    // searching by genres
    $query->where(function ($query) use ($genres) {
      $query->where(\DB::raw('1'), \DB::raw('0'));
      foreach ($genres as $genre) {
        if ($genre !== 'Unknown') {
          $query->orWhere(function ($query) use ($genre) {
            $query->whereLike('genres', '%'.json_encode($genre).'%');
          });
        }
        else {
          $query->orWhere('genres', '[]');
        }
      }
    });

    // searching by ratings
    $query->where(function ($query) use ($ratings) {
      $query->whereIn('rating', $ratings);
      if ($ratings->contains('Unknown')) {
        $query->orWhere('rating', null);
      }
    });

    // searching by title
    if ($search !== '') {
      $query->where(function ($query) use ($search, $fill) {

        // match with full titles
        $query->where(function ($query) use ($search) {
          $query->withTitle($search, false);
        });

        // match with partial titles
        $query->orWhere(function ($query) use ($search) {
          $query->withTitle($search, true);
        });

        // match with partial titles, with non-alphanumeric characters ignored
        $thisSearch = str_to_url($search, '%', '/[^a-zA-Z0-9]/u');
        if (strlen(str_replace('%', '', $thisSearch)) >= 1) {
          $query->orWhere(function ($query) use ($thisSearch) {
            $query->withTitle($thisSearch, true);
          });
        }

        // match with partial titles, with non-alphabetic characters ignored
        $thisSearch = str_to_url($search, '%', '/[^a-zA-Z]/u');
        if (strlen(str_replace('%', '', $thisSearch)) >= 1) {
          $query->orWhere(function ($query) use ($thisSearch) {
            $query->withTitle($thisSearch, true);
          });
        }

        if ($fill) {
          // match any titles with the same letters in the same order, at any location
          $thisSearch = str_to_url($search, '%', '/[^a-zA-Z]/u');
          if (strlen(str_replace('%', '', $thisSearch)) >= 1) {
            $thisSearch = '%'.str_to_url($thisSearch, '$1%', '/([a-zA-Z])/u');
            $query->orWhere(function ($query) use ($thisSearch) {
              $query->withTitle($thisSearch, true);
            });
          }
        }

      });
    }

    // complete and return results
    $results = $query->get();

    if ($search !== '') {
      foreach ($results as $result) {
        $result->matchScore = 0;
        foreach ($result->alts as $alt) {
          similar_text(mb_strtolower($alt), $search, $matchScore);
          if ($matchScore > $result->matchScore) {
            $result->matchScore = $matchScore;
          }
        }
      }
      $results = $results->sortByDesc('matchScore');
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
      if ($title === '') {
        return $show;
      }
      foreach ($show->alts as $alt) {
        if ($title === slugify($alt)) {
          return $show;
        }
      }
    }

    if ($title !== '') {
      $title = deslugify($title);
      $show = Show::withTitle($title)->first();
      if (isset($show)) {
        return $show;
      } else {
        $show = ShowManager::addShowWithTitle($title, false);
        return $show;
      }
    }

    return null;
  }

  private static function relatedToShow($related) {
    $show = Show::where('mal_id', $related['mal_id'])->first();
    if ($show === null) {
      $show = new Show();
      $show->mal = true;
      $show->mal_id = $related['mal_id'];
      $show->title = $related['title'];
    }
    return $show;
  }

  /**
  * Convert the series this show belongs to to a directed graph in DOT notation.
  *
  * @return string
  */
  public function seriesDot() {
    $string = 'digraph '.$this->mal_id.' {\n';
    $collection = collect([]);
    $this->seriesDotRecursive($string, $collection);
    $string .= '}\n';
    return $string;
  }
  private function seriesDotRecursive(& $string, & $visited) {
    // Visit this node if it has not been added yet
    if (!$visited->has($this->mal_id)) {
      $visited[$this->mal_id] = $visited->count() + 1;
      $string .= '  '.$visited[$this->mal_id].' [label="'.$this->title.'"]\n';

      $this->seriesDotRelation($string, $visited, 'prequels', 'Prequel');
      $this->seriesDotRelation($string, $visited, 'sequels', 'Sequel');
      $this->seriesDotRelation($string, $visited, 'summaries', 'Summary');
      $this->seriesDotRelation($string, $visited, 'specials', 'Special');
      $this->seriesDotRelation($string, $visited, 'alternatives', 'Alternative');
      $this->seriesDotRelation($string, $visited, 'others', 'Other');
    }
  }
  private function seriesDotRelation(& $string, & $visited, $relation, $relationFancy) {
    // For all related nodes
    foreach ($this->$relation as $show) {
      // Make sure they are added to the database
      $show = Self::relatedToShow($show);
      if ($show->mal) {
        $show = ShowManager::addShowWithMalId($show->mal_id);
      }
      // Call function on those nodes
      $show->seriesDotRecursive($string, $visited);
      // Add edges to those nodes
      $string .= '  '.$visited[$this->mal_id].' -> '.$visited[$show->mal_id].' [label="'.$relationFancy.'"]\n';
    }
  }

  /**
  * Get an array of this series prequels and sequels in order.
  *
  * @return array
  */
  public function seriesMap($shows = []) {
    foreach ($this->prequels as $prequel) {
      $done = false;
      foreach ($shows as $show) {
        if ($show->mal_id == $prequel['mal_id']) {
          $done = true;
          break;
        }
      }
      if (!$done) {
        $quelShow = Self::relatedToShow($prequel);
        if (!$quelShow->mal) {
          $shows = $quelShow->seriesMap($shows);
        } else {
          $shows[] = $quelShow;
        }
      }
    }

    $done = false;
    foreach ($shows as $show) {
      if ($show->mal_id == $this->mal_id) {
        $done = true;
        break;
      }
    }
    if (!$done) {
      $shows[] = $this;
    }

    foreach ($this->sequels as $sequel) {
      $done = false;
      foreach ($shows as $show) {
        if ($show->mal_id == $sequel['mal_id']) {
          $done = true;
          break;
        }
      }
      if (!$done) {
        $quelShow = Self::relatedToShow($sequel);
        if (!$quelShow->mal) {
          $shows = $quelShow->seriesMap($shows);
        } else {
          $shows[] = $quelShow;
        }
      }
    }

    return $shows;
  }

  /**
  * Get all the related shows by type, excluding prequels and sequels.
  *
  * @return array
  */
  public function getRelatedAttribute() {
    $relatedShows = [];
    foreach ($this->alternatives as $related) {
      $relatedShows['Alternatives'][] = Self::relatedToShow($related);
    }
    foreach ($this->summaries as $related) {
      $relatedShows['Summaries'][] = Self::relatedToShow($related);
    }
    foreach ($this->specials as $related) {
      $relatedShows['Specials'][] = Self::relatedToShow($related);
    }
    foreach ($this->others as $related) {
      $relatedShows['Others'][] = Self::relatedToShow($related);
    }
    return $relatedShows;
  }

  /**
  * Get whether the show is currently airing for the requested translation type.
  *
  * @return boolean
  */
  public function isAiring($translation_type) {
    $latest = 'latest_'.$translation_type;
    $latest = $this->$latest;
    return $latest !== null && (!isset($this->episode_amount) || $latest->episode_num < $this->episode_amount);
  }

  /**
  * Get whether the show has finished airing for the requested translation type.
  *
  * @return boolean
  */
  public function finishedAiring($translation_type) {
    $latest = 'latest_'.$translation_type;
    $latest = $this->$latest;
    return $latest !== null && isset($this->episode_amount) && $latest->episode_num >= $this->episode_amount;
  }

  /**
  * Get the estimated date and time of the next uploaded episode.
  *
  * @return Carbon\Carbon
  */
  public function nextUploadEstimate($translation_type) {
    if ($this->isAiring($translation_type)) {
      $difference = $this->episodeDelayEstimate($translation_type);
      if ($difference !== null) {
        return $this->{'latest_'.$translation_type}->uploadtime->addDays($difference);
      }
    }

    elseif (!$this->finishedAiring($translation_type) && $translation_type === 'sub' && isset($this->airing_start)) {
      return $this->airing_start;
    }

    return null;
  }

  /**
  * Get the estimated delay between episodes, in days.
  *
  * @return integer
  */
  public function episodeDelayEstimate($translation_type) {
    if ($this->airing_type === 'weekly') {
      return 7;
    }
    elseif ($this->airing_type === 'once') {
      return 0;
    }

    elseif ($this->airing_start !== null && $this->airing_end !== null && $this->episode_amount !== null && $this->episode_amount > 1 && $translation_type === 'sub') {
      return (int) round($this->airing_start->diffInDays($this->airing_end) / ($this->episode_amount - 1));
    }

    else {
      $uploadtimes = $this->episodes($translation_type, 'asc')->pluck('uploadtime');
      if ($uploadtimes->count() > 1) {
        $differences = collect([]);
        $lastUploadtime = null;
        foreach ($uploadtimes as $uploadtime) {
          if (isset($lastUploadtime)) {
            $differences->put($lastUploadtime->diffInDays($uploadtime), $differences->get($lastUploadtime->diffInDays($uploadtime)) + 1);
          }
          $lastUploadtime = $uploadtime;
        }
        $max = $differences->max();
        $differencesMax = collect([]);
        while ($differences->search($max) !== false) {
          $differencesMax[] = $differences->search($max);
          $differences->forget($differences->search($max));
        }
        return $differencesMax->max();
      }
    }

    return null;
  }

  /**
  * Get the estimated day of broadcasts if this show broadcasts weekly.
  *
  * @return string
  */
  public function broadcastDayEstimate($translation_type) {
    switch ($this->expandedAiringType($translation_type)) {
      case 'irregular':
        return 'irregular';
      break;
      case 'once':
        return 'once';
      break;
      case 'weekly':
        if ($this->{'latest_'.$translation_type} !== null) {
          return mb_strtolower($this->{'latest_'.$translation_type}->uploadtime->format('l'));
        } elseif ($this->airing_start !== null && $translation_type === 'sub') {
          return mb_strtolower($this->airing_start->format('l'));
        }
      break;
    }
    return null;
  }

  /**
  * Get the latest subbed episode for this show.
  *
  * @return App\Show
  */
  public function getLatestSubAttribute() {
    return $this->videos()
                ->where('translation_type', 'sub')
                ->orderBy('episode_num', 'desc')
                ->orderBy('uploadtime', 'asc')
                ->orderBy('id', 'asc')
                ->first();
  }

  /**
  * Get the latest dubbed episode for this show.
  *
  * @return App\Show
  */
  public function getLatestDubAttribute() {
    return $this->videos()
                ->where('translation_type', 'dub')
                ->orderBy('episode_num', 'desc')
                ->orderBy('uploadtime', 'asc')
                ->orderBy('id', 'asc')
                ->first();
  }

  /**
  * Get the first uploaded video.
  *
  * @return App\Video
  */
  public function getFirstVideoAttribute() {
    return $this->videos()->orderBy('uploadtime', 'asc')->orderBy('id', 'asc')->first();
  }

  /**
  * Get the last uploaded video.
  *
  * @return App\Video
  */
  public function getLastVideoAttribute() {
    return $this->videos()->orderBy('uploadtime', 'desc')->orderBy('id', 'desc')->first();
  }


  /**
  * Get a list of episodes of the requested type.
  *
  * @return Illuminate\Database\Eloquent\Collection
  */
  public function episodes($translation_type, $order = 'desc', $episode_num_min = null) {
    $query = $this->videos()->where('translation_type', $translation_type);

    if (isset($episode_num_min)) {
      $query->where('episode_num', '>', $episode_num_min);
    }

    return $query->distinctOn('episode_num', ['uploadtime' => 'asc', 'id' => 'asc'])
                 ->orderBy('episode_num', $order)
                 ->orderBy('uploadtime', 'asc')
                 ->orderBy('id', 'asc')
                 ->get();
  }

  /**
  * Get the full url for this show's details page.
  *
  * @return string
  */
  public function getDetailsUrlAttribute() {
    if (empty($this->mal)) {
      return fullUrl('/anime/'.$this->id.'/'.slugify($this->title));
    } else {
      return $this->mal_url;
    }
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
  * Get the full url for this show's series overview page.
  *
  * @return string
  */
  public function getSeriesUrlAttribute() {
    if (empty($this->mal)) {
      return $this->details_url.'/series';
    } else {
      return null;
    }
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
  * Get the url to the MAL page to edit the user data for this show.
  *
  * @return string
  */
  public function getMalEditUrlAttribute() {
    if (isset($this->mal_id)) {
      return 'https://myanimelist.net/editlist.php?type=anime&id='.$this->mal_id;
    } else {
      return null;
    }
  }

  /**
  * Get the url to this show's primary local thumbnail.
  *
  * @return string
  */
  public function getThumbnailUrlAttribute() {
    if (count($this->local_thumbnail_ids) > 0) {
      return fullUrl('/media/thumbnails/'.$this->local_thumbnail_ids[0]);
    } elseif (count($this->remote_thumbnail_urls) > 0) {
      return $this->remote_thumbnail_urls[0];
    } else {
      return fullUrl('/media/no_thumbnail.png');
    }
  }

  /**
  * Check whether MAL still has an anime with this MAL id.
  *
  * @return boolean
  */
  public function getMalLinkedAttribute() {
    if (isset($this->mal_id)) {
      $page = Downloaders::downloadPage($this->mal_url);
      if (!str_contains($page, '404 Not Found')) {
        return true;
      }
    }
    return false;
  }

  /**
   * Update this show's cached infomation only if the cache time has expired.
   */
  public function handleCaching() {
    // TODO: smarter cache time
    if (isset($this->cache_updated_at) && $this->cache_updated_at->diffInHours(Carbon::now()) >= rand(168, 336)) {
      queueJob(new \App\Jobs\ShowUpdate($this->id), 'high');
    }
  }

  /**
   * Handle caching calls.
   */
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
  public function getSeasonAttribute($value) {
    $this->handleCaching();
    return $value;
  }
}
