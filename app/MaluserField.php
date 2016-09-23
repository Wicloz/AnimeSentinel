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

  ];

  /**
   * The attributes that should be casted to native types.
   *
   * @var array
   */
  protected $casts = [

  ];
}
