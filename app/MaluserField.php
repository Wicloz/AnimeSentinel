<?php

namespace App;

class MaluserField extends BaseModel
{
  public $primaryKey = ['mal_id', 'user_id'];
  public $incrementing = false;

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'mal_id', 'mal_show', 'nots_mail_setting', 'nots_mail_notified',
  ];

  /**
   * The attributes that should be casted to native types.
   *
   * @var array
   */
  protected $casts = [
    'mal_show' => 'collection',
    'nots_mail_notified' => 'collection',
  ];
}
