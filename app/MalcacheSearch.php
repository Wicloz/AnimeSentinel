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
   * Properly serialize and unserialize the results attribute.
   */
  public function getResultsAttribute($value) {
    return unserialize(json_decode($value));
  }
  public function setResultsAttribute($value) {
    $this->attributes['results'] = json_encode(serialize($value));
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

      if (empty($search->results) || count($search->results) <= 0 || $search->cache_updated_at->diffInHours(Carbon::now()) >= rand(24, 48)) {
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
