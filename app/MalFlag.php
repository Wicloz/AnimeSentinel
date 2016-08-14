<?php

namespace App;

class MalFlag extends BaseModel
{
  public $primaryKey = 'mal_id';
  public $incrementing = false;

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'mal_id', 'is_hentai', 'is_music',
  ];

  /**
  * Set the flags.
  */
  public function setFlags() {
    $flags = \App\AnimeSentinel\MyAnimeList::malFlags($this->mal_id);
    $this->is_hentai = $flags->hentai;
    $this->is_music = $flags->music;
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

  /**
  * Returns whether this item has any flag set.
  *
  * @return boolean
  */
  public function getFlaggedAttribute() {
    return $this->is_hentai || $this->is_music;
  }
}
