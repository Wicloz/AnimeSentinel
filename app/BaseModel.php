<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use DB;

abstract class BaseModel extends Model
{
  public function __construct(array $attributes = []) {
    parent::__construct($attributes);
  }

  // NOTE: must not be followed by ->where() or ->select()
  public function scopeDistinctOn($query, $columns) {
    if (!is_array($columns)) {
      $columns = [$columns];
    }

    switch (config('database.default')) {
      case 'pgsql':
        $inserts = [];
        foreach ($columns as $column) {
          $inserts[] = '?';
        }

        $query->select(DB::raw(
          '* FROM (SELECT DISTINCT ON ('.implode(', ', $columns).') * '
        ));

        $query->where(DB::raw(
          '1=1 ORDER BY '.implode(', ', $columns).') d where 1'
        ), '=', '1');
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

  public function scopeWhereLike($query, $attribute, $like) {
    switch (config('database.default')) {
      case 'pgsql':
        $operator = 'ilike';
      break;
      default:
        $operator = 'like';
      break;
    }
    return $query->where($attribute, $operator, $like);
  }
}
