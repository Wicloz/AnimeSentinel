<?php

namespace App;

class MalcacheSearch extends BaseModel
{
  /**
   * The table associated with the model.
   *
   * @var string
   */
  protected $table = 'malcache_search';

  public $primaryKey = 'mal_id';
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
    return json_decode($value);
  }
  public function setResultsAttribute($value) {
    $this->attributes['results'] = json_encode($value);
  }
}
