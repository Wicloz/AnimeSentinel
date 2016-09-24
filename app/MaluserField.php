<?php

namespace App;

class MaluserField extends BaseModel
{
  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'user_id', 'mal_id', 'mal_show', 'auto_watching_changed', 'nots_mail_setting', 'nots_mail_notified',
  ];

  /**
   * The attributes that should be casted to native types.
   *
   * @var array
   */
  protected $casts = [
    'nots_mail_notified' => 'collection',
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
