<?php

namespace App;

use App\AnimeSentinel\MyAnimeList;
use Carbon\Carbon;

class MalcacheSearch extends BaseModel
{
  /**
   * The table associated with the model.
   *
   * @var string
   */
  protected $table = 'malcache_search';

  public $primaryKey = 'query';
  public $incrementing = false;

  /**
   * The attributes that should be mutated to dates.
   *
   * @var array
   */
  protected $dates = ['cache_updated_at', 'created_at', 'updated_at'];

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'query', 'results',
  ];

  /**
   * The attributes that should be casted to native types.
   *
   * @var array
   */
  protected $casts = [
    'results' => 'collection',
  ];

  /**
   * Properly decode the results attribute.
   */
  public function getResultsAttribute($value) {
    $results = json_decode($value);
    if (isset($results)) {
      foreach ($results as $index => $result) {
        if (isset($result->airing_start)) {
          $airing_start = serialize($result->airing_start);
          $airing_start = preg_replace('@^O:8:"stdClass":@','O:13:"Carbon\Carbon":', $airing_start);
          $result->airing_start = unserialize($airing_start);
        }
        if (isset($result->airing_end)) {
          $airing_end = serialize($result->airing_end);
          $airing_end = preg_replace('@^O:8:"stdClass":@','O:13:"Carbon\Carbon":', $airing_end);
          $result->airing_end = unserialize($airing_end);
        }
        $results[$index] = $result;
      }
    }
    return collect($results);
  }

  /**
   * Returns and caches search results from MAL.
   * These are returned from the cache whenever possible.
   *
   * @return array
   *
   * TODO: add filters
   */
  public static function search($query, $start = 0, $amount = null) {
    if ($query !== '') {
      while (strlen($query) < 3) {
        $query .= ' ';
      }
      $search = Self::firstOrNew(['query' => $query]);

      if (count($search->results) <= 0 || $search->cache_updated_at->diffInHours(Carbon::now()) >= rand(24, 48)) {
        $search->results = MyAnimeList::search($query);
        $search->cache_updated_at = Carbon::now();
        $search->save();
      }

      return $search->results->slice($start, $amount);
    }

    else {
      return [];
    }
  }
}
