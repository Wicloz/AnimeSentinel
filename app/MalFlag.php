<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MalFlag extends Model
{
  public $primaryKey = 'mal_id';
  public $incrementing = false;

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'mal_id', 'is_hentai',
  ];

  /**
  * Set the is_hentai flag.
  */
  public function setIsHentai() {
    $this->is_hentai = \App\AnimeSentinel\MyAnimeList::isHentai($this->mal_id);
    return $this;
  }

  /**
  * Get the show this flag belongs to.
  *
  * @return \Illuminate\Database\Eloquent\Relations\Relation
  */
  public function show() {
    return $this->belongsTo(Show::class, 'mal_id', 'mal_id');
  }
}
