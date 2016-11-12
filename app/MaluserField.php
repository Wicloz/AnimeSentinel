<?php

namespace App;

class MaluserField extends BaseModel
{
  use Traits\HasCompositePrimaryKey;
  public $primaryKey = ['user_id', 'mal_id'];
  public $incrementing = false;

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'user_id', 'mal_id', 'mal_show', 'auto_watching_changed', 'nots_mail_setting', 'nots_mail_notified_sub', 'nots_mail_notified_dub',
  ];

  /**
   * The attributes that should be casted to native types.
   *
   * @var array
   */
  protected $casts = [
    'nots_mail_notified_sub' => 'collection',
    'nots_mail_notified_dub' => 'collection',
    'mal_show' => 'collection',
  ];

  /**
  * Get the user for this mal field.
  *
  * @return \Illuminate\Database\Eloquent\Relations\Relation
  */
  public function user() {
    return $this->belongsTo(User::class);
  }

  /**
  * Get the show for this mal field.
  *
  * @return \Illuminate\Database\Eloquent\Relations\Relation
  */
  public function show() {
    return $this->belongsTo(Show::class, 'mal_id', 'mal_id');
  }

  /**
   * Properly encode and decode the mal_show.
   */
  public function getMalShowAttribute($value) {
    return json_decode($value);
  }
  public function setMalShowAttribute($value) {
    $this->attributes['mal_show'] = json_encode($value);
  }

  /**
  * Convert this field's mal information to a Show model.
  *
  * @return \App\Show
  */
  public function toShow() {
    $show = new Show();
    $show->mal = true;
    $show->mal_id = $this->mal_show->mal_id;
    $show->title = $this->mal_show->title;
    $show->remote_thumbnail_urls = $this->mal_show->remote_thumbnail_urls;
    $show->mal_show = $this->mal_show;
    return $show;
  }

  /**
  * Return the setting for this mal show's mail notifications combined with the default.
  *
  * @return string
  */
  public function getNotsMailSettingCombinedAttribute() {
    if ($this->nots_mail_setting !== null) {
      return $this->nots_mail_setting;
    } else {
      return $this->user->nots_mail_settings[$this->mal_show->status];
    }
  }

  /**
  * Returns whether the user wants to recieve notifications for the requested type.
  *
  * @return boolean
  */
  public function notsMailWantsForType($type) {
    return $this->nots_mail_setting_combined === 'both' || $this->nots_mail_setting_combined === $type || ($type === 'any' && $this->nots_mail_setting_combined !== 'none');
  }
}
