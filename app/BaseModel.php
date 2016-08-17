<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use DB;

abstract class BaseModel extends Model
{
  public function __construct(array $attributes = []) {
    parent::__construct($attributes);
  }

  public function scopeDistinctOn($query, $columns, $table) {
    if (!is_array($columns)) {
      $columns = [$columns];
    }

    switch (config('database.default')) {
      case 'pgsql':
        $inserts = [];
        foreach ($columns as $column) {
          $inserts[] = '?';
        }
        $query->from(DB::raw(
          '(SELECT DISTINCT ON ('.implode(', ', $columns).') * FROM '.$table.' ORDER BY '.implode(', ', $columns).') dis'
        ), array_merge($columns, $columns));
      break;

      case 'mysql':
        return $query->groupBy($columns)->distinct();
      break;

      default:
        dd('\'distinctOn\' is not supported for databases of type \''.config('database.default').'\'');
      break;
    }
  }

  public function scopeRandom($query) {
    switch (config('database.default')) {
      case 'pgsql':
        return $query->orderBy(DB::raw('RANDOM()'));
      break;

      case 'mysql':
        return $query->orderBy(DB::raw('RAND()'));
      break;

      default:
        dd('\'random\' is not supported for databases of type \''.config('database.default').'\'');
      break;
    }
  }
}
