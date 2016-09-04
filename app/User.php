<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

use App\AnimeSentinel\Helpers;
use App\AnimeSentinel\Downloaders;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use App\Show;
use Illuminate\Database\Eloquent\Collection;

class User extends Authenticatable
{
  use Notifiable;

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'username', 'email', 'password', 'mal_user', 'mal_pass', 'mal_canread', 'mal_canwrite', 'mal_list', 'nots_mail_state', 'nots_mail_settings_general', 'nots_mail_settings_specific', 'auto_watching',
  ];

  /**
   * The attributes that should be casted to native types.
   *
   * @var array
   */
  protected $casts = [
    'mal_list' => 'array',
    'nots_mail_settings_general' => 'array',
    'nots_mail_settings_specific' => 'array',
  ];

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
   * Return the user's cached MAL list, with shows.
   *
   * @return Illuminate\Database\Eloquent\Collection
   */
  public function getMalListAttribute($value) {
    $value = collect(json_decode($value));
    $shows = Show::whereIn('mal_id', $value->pluck('mal_id'))->get();

    foreach ($value as $index => $anime) {
      $value[$index]->show = $shows->where('mal_id', $anime->mal_id)->first();
    }

    return $value;
  }

  /**
   * Return the user's cached MAL list, without shows.
   *
   * @return Illuminate\Database\Eloquent\Collection
   */
  public function getMalListMinAttribute($value) {
    $value = collect(json_decode($value));
    return $value;
  }

  /**
   * Properly store a MAL list for caching.
   */
  public function setMalListAttribute($value) {
    foreach ($value as $index => $anime) {
      unset($value[$index]->show);
    }
    $this->attributes['mal_list'] = json_encode($value);
  }

  /**
   * Return the specific mail notification settings list with missing values replaced by null.
   *
   * @return array
   */
  public function getNotsMailSettingsSpecificAttribute($value) {
    foreach ($this->mal_list_min as $mal_show) {
      if (!array_key_exists($mal_show->mal_id, $value)) {
        $value[$mal_show->mal_id] = null;
      }
    }
  }

  /**
   * Return the item from the mal list with the requested MAL id
   * Only use when you only need a single item
   *
   * @return stdClass
   */
  public function mal_show($mal_id) {
    $mal_show = $this->mal_list_min->where('mal_id', $mal_id)->first();
    $mal_show->show = Show::where('mal_id', $mal_show->mal_id)->get();
    return $mal_show;
  }

  /**
   * Update this user's cached MAL list and credential status.
   */
  public function updateCache() {
    $results = $this->getMalList(true);
    if ($results === false) {
      $this->mal_list = new Collection();
    } else {
      $this->mal_list = $results;
    }
    $this->save();
  }

  /**
   * Download and parse this user's MAL list.
   *
   * @return Illuminate\Database\Eloquent\Collection
   */
  public function getMalList($checkCredentials = false) {
    // Download the page
    $page = Downloaders::downloadPage('https://myanimelist.net/animelist/'.$this->mal_user);
    // Check whether the page is valid, return false if it isn't
    if (str_contains($page, 'Invalid Username Supplied') || str_contains($page, 'Access to this list has been restricted by the owner') || str_contains($page, '404 Not Found - MyAnimeList.net')) {
      $this->mal_canread = false;
      $this->save();
      return false;
    } else {
      $this->mal_canread = true;
      $this->save();
    }
    // If it is requested, check write permissions
    if ($checkCredentials) {
      $this->postToMal('validate', 0);
    }

    // Grab and decode anime list
    $results = collect(json_decode(str_get_between($page, '<table class="list-table" data-items="', '">')));
    // Get all related shows from our database
    $shows = Show::whereIn('mal_id', $results->pluck('anime_id'))->get();

    // Convert the results to more convenient objects
    $animes = new Collection();
    foreach ($results as $result) {
      $anime = new \stdClass();

      switch ($result->status) {
        case '1':
          $anime->status = 'watching';
        break;
        case '2':
          $anime->status = 'completed';
        break;
        case '3':
          $anime->status = 'onhold';
        break;
        case '4':
          $anime->status = 'dropped';
        break;
        case '5':
          $anime->status = 'ptw';
        break;
      }

      $anime->show = $shows->where('mal_id', $result->anime_id)->first();

      $anime->mal_id = $result->anime_id;
      $anime->title = $result->anime_title;
      $anime->eps_watched = $result->num_watched_episodes;
      $animes[] = $anime;
    }

    return $animes;
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

    if ($response === 'Invalid credentials') {
      $this->mal_canwrite = false;
      $this->save();
      return false;
    } else {
      $this->mal_canwrite = true;
      $this->save();
      return $response;
    }
  }

  /**
   * Update the MAL caches and send notifications.
   */
  public function periodicTasks() {
    // Update the MAL cache
    // $this->updateCache();

    // Send mail notifications
    if ($this->nots_mail_state) {
      // For all shows on the user's mal list
      foreach ($this->mal_list as $mal_show) {
        // If the user want to recieve notifications for this show
        // and we have the show
        // and there is a newer episode available
        // and we did not already send a mail
        if (
          (
            ($this->nots_mail_settings_general[$mal_show->status] && (!array_key_exists($mal_show->mal_id, $this->nots_mail_settings_specific[$mal_show->status]) || $this->nots_mail_settings_specific[$mal_show->status][$mal_show->mal_id])) ||
            (!$this->nots_mail_settings_general[$mal_show->status] && array_key_exists($mal_show->mal_id, $this->nots_mail_settings_specific[$mal_show->status]) && $this->nots_mail_settings_specific[$mal_show->status][$mal_show->mal_id])
          ) &&
          isset($mal_show->show) &&
          (
            (isset($mal_show->show->latest_sub) && $mal_show->eps_watched < $mal_show->show->latest_sub->episode_num) ||
            (isset($mal_show->show->latest_dub) && $mal_show->eps_watched < $mal_show->show->latest_dub->episode_num)
          )
        ) {
          // Send a notification mail (TODO)
          \Mail::send('emails.report_general', ['description' => 'New Episode Available', 'vars' => [
            'Show Title' => $mal_show->show->title,
            'Latest Sub' => $mal_show->show->latest_sub->episode_num,
            'Latest Dub' => $mal_show->show->latest_dub->episode_num,
          ]], function ($m) {
            $m->subject('New episode of anime \''.$mal_show->show->title.'\' available');
            $m->from('reports.animesentinel@wilcodeboer.me', 'AnimeSentinel Notifications');
            $m->to('animesentinel@wilcodeboer.me');
          });
        }
      }
    }

  }
}
