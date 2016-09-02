<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

use App\AnimeSentinel\Helpers;
use App\AnimeSentinel\Downloaders;

class User extends Authenticatable
{
  use Notifiable;

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'username', 'email', 'password', 'mal_user', 'mal_pass', 'mal_status', 'mal_last_checked', 'nots_mail_state', 'nots_mail_settings_general', 'nots_mail_settings_specific', 'auto_watching',
  ];

  /**
   * The attributes that should be casted to native types.
   *
   * @var array
   */
  protected $casts = [
    'nots_mail_settings_general' => 'array',
    'nots_mail_settings_specific' => 'array',
    'mal_status' => 'array',
  ];

  /**
   * The attributes that should be mutated to dates.
   *
   * @var array
   */
  protected $dates = ['mal_last_checked', 'created_at', 'updated_at'];

  /**
   * The attributes that should be hidden for arrays.
   *
   * @var array
   */
  protected $hidden = [
    'password', 'mal_pass', 'remember_token',
  ];

  /**
   * Handle encryption of the users MAL password.
   */
  public function getMalPassAttribute($value) {
    return decrypt($value);
  }
  public function setMalPassAttribute($value) {
    $this->attributes['mal_pass'] = encrypt($value);
  }

  /**
   * Send a post request to MAL with the requested data.
   *
   * @return string
   */
  private function postToMal($url, $data = null) {
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_USERNAME, $this->mal_user);
    curl_setopt($curl, CURLOPT_PASSWORD, $this->mal_pass);
    $response = curl_exec($curl);
    curl_close($curl);
    return $response;
  }

  /**
   * Check the validility of the users MAL credentials.
   *
   * @return boolean
   */
  public function malCanWrite() {
    if (true) {
      $this->mal_status = [
        'canRead' => $this->malCanRead(),
        'canWrite' => $this->postToMal('https://myanimelist.net/api/account/verify_credentials.xml') !== 'Invalid credentials',
      ];
      $this->save();
    }
    return $this->mal_status['canWrite'];
  }

  /**
   * Check the validility of the users MAL username.
   *
   * @return boolean
   */
  public function malCanRead() {
    if (true) {
      $this->mal_status = [
        'canRead' => !str_contains(Downloaders::downloadPage('https://myanimelist.net/animelist/'.$this->mal_user), 'Invalid Username Supplied'),
        'canWrite' => $this->malCanWrite(),
      ];
      $this->save();
    }
    return $this->mal_status['canRead'];
  }
}
