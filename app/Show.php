<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Show extends Model
{
  public function videos() {
    return $this->hasMany(Video::class);
  }
}
