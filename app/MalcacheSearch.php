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
   * Encode and decode the results attribute.
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
    return $results;
  }
  public function setResultsAttribute($value) {
    $this->attributes['results'] = json_encode($value);
  }

  /**
   * Returns and caches search results from MAL.
   * These are returned from the cache whenever possible.
   *
   * @return array
   */
  public static function search($query, $limit) {
    $query = mb_strtolower($query);
    $search = Self::firstOrNew(['query' => $query]);

    if (empty($search->results) || $search->cache_updated_at->diffInHours(Carbon::now()) >= rand(24, 48)) {
      $search->results = MyAnimeList::search($query, 64);
      $search->cache_updated_at = Carbon::now();
      $search->save();
    }

    return collect(array_slice($search->results, 0, $limit));
  }
}
