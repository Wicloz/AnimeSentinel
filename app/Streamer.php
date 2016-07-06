<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Streamer extends Model
{
  public function videos() {
    return $this->hasMany(Video::class);
  }
}
