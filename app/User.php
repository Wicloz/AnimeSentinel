<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

use App\AnimeSentinel\Helpers;
use App\AnimeSentinel\Downloaders;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class User extends Authenticatable
{
  use Notifiable;

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'username', 'email', 'password', 'mal_user', 'mal_pass', 'mal_status', 'mal_status_updated', 'nots_mail_state', 'nots_mail_settings_general', 'nots_mail_settings_specific', 'auto_watching',
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
  protected $dates = ['mal_status_updated', 'created_at', 'updated_at'];

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
   * Download and parse this user's MAL list.
   *
   * @return array
   */
  public function getMalList() {
    $page = Downloaders::downloadPage('https://myanimelist.net/animelist/'.$this->mal_user);
    if (str_contains($page, 'Invalid Username Supplied') || str_contains($page, 'Access to this list has been restricted by the owner') || str_contains($page, '404 Not Found - MyAnimeList.net')) {
      $this->checkMalCredentials(false, null);
      return false;
    }

    return [];
  }

  /**
   * Send a post request to the MAL api with the requested data.
   *
   * @return string
   */
  public function postToMal($task, $id, $data = null) {
    if ($task === 'validate') {
      $url = 'https://myanimelist.net/api/account/verify_credentials.xml';
    } else {
      $url = 'https://myanimelist.net/api/animelist/'.$task.'/'.$id.'.xml';
    }

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_USERNAME, $this->mal_user);
    curl_setopt($curl, CURLOPT_PASSWORD, $this->mal_pass);
    $response = curl_exec($curl);
    curl_close($curl);

    if ($task !== 'validate' && $response === 'Invalid credentials') {
      $this->checkMalCredentials(null, false);
      return false;
    }

    return $response;
  }

  /**
   * Determine whether we should be able to modify this user's MAL list.
   *
   * @return boolean
   */
  public function getMalShouldReadAttribute() {
    return !empty($this->mal_user);
  }

  /**
   * Determine whether we should be able to read this user's MAL list.
   *
   * @return boolean
   */
  public function getMalShouldWriteAttribute() {
    return !empty($this->mal_user) && !empty($this->mal_pass);
  }

  /**
   * Check whether the current MAL credentials are valid.
   */
  public function checkMalCredentials($canRead = null, $canWrite = null) {
    if ($canRead === null) {
      $animeListPage = Downloaders::downloadPage('https://myanimelist.net/animelist/'.$this->mal_user);
      $canRead = !str_contains($animeListPage, 'Invalid Username Supplied') && !str_contains($animeListPage, 'Access to this list has been restricted by the owner') && !str_contains($animeListPage, '404 Not Found - MyAnimeList.net');
    }
    if ($canWrite === null) {
      $canWrite = $this->postToMal('validate', 0) !== 'Invalid credentials';
    }

    $this->mal_status = [
      'canRead' => $canRead,
      'canWrite' => $canWrite,
      'credentials' => Hash::make($this->mal_user.'-'.$this->mal_pass),
      'updated_at' => Carbon::now()->timestamp,
    ];
    $this->save();
  }

  /**
   * Get whether we can read this user's MAL list.
   * Only to be used for user notifications etc.
   *
   * @return boolean
   */
  public function getMalCanReadAttribute() {
    if (Carbon::createFromFormat('U', $this->mal_status['updated_at'])->diffInHours(Carbon::now()) >= rand(120, 168) ||
        !Hash::check($this->mal_user.'-'.$this->mal_pass, $this->mal_status['credentials']))
    {
      $this->checkMalCredentials();
    }
    return $this->mal_status['canRead'];
  }

  /**
   * Get whether we can modify this user's MAL list.
   * Only to be used for user notifications etc.
   *
   * @return boolean
   */
  public function getMalCanWriteAttribute() {
    if (Carbon::createFromFormat('U', $this->mal_status['updated_at'])->diffInHours(Carbon::now()) >= rand(120, 168) ||
        !Hash::check($this->mal_user.'-'.$this->mal_pass, $this->mal_status['credentials']))
    {
      $this->checkMalCredentials();
    }
    return $this->mal_status['canWrite'];
  }
}
