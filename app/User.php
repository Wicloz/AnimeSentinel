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
    'username', 'email', 'password', 'mal_user', 'mal_pass', 'mal_canread', 'mal_canwrite', 'mal_list', 'nots_mail_state', 'nots_mail_settings_state_general', 'nots_mail_settings_state_specific', 'nots_mail_settings_ttype_general', 'nots_mail_settings_ttype_specific', 'nots_mail_notified', 'auto_watching', 'auto_watching_changed',
  ];

  /**
   * The attributes that should be casted to native types.
   *
   * @var array
   */
  protected $casts = [
    'mal_list' => 'collection',
    'nots_mail_settings_state_general' => 'collection',
    'nots_mail_settings_state_specific' => 'collection',
    'nots_mail_settings_ttype_general' => 'collection',
    'nots_mail_settings_ttype_specific' => 'collection',
    'nots_mail_notified' => 'collection',
    'auto_watching_changed' => 'collection',
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
  public function getMalListMinAttribute() {
    $value = collect(json_decode($this->attributes['mal_list']));
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
   * Return the combined mail notification setting for a specific mal show.
   *
   * @return boolean
   */
  public function nots_mail_state_for($mal_show) {
    return $this->nots_mail_settings_state_specific->get($mal_show->mal_id) === true ||
    ($this->nots_mail_settings_state_specific->get($mal_show->mal_id) === null && $this->nots_mail_settings_state_general->get($mal_show->status) === true);
  }

  /**
   * Return whether the user wants to recieve mail notifications for the requested translation type for the requested show.
   *
   * @return boolean
   */
  public function nots_mail_wants_ttype($mal_show, $ttype) {
    return $this->nots_mail_settings_ttype_specific->get($mal_show->mal_id) === 'both' || $this->nots_mail_settings_ttype_specific->get($mal_show->mal_id) === $ttype ||
    ($this->nots_mail_settings_ttype_specific->get($mal_show->mal_id) === null && (
      $this->nots_mail_settings_ttype_general->get($mal_show->status) === 'both' || $this->nots_mail_settings_ttype_general->get($mal_show->status) === $ttype
    ));
  }

  /**
   * Return the item from the mal list with the requested MAL id
   * Only use when you only need a single item
   *
   * @return stdClass
   */
  public function mal_show($mal_id) {
    $mal_show = $this->mal_list_min->where('mal_id', $mal_id)->first();
    if (isset($mal_show)) {
      $mal_show->show = Show::where('mal_id', $mal_show->mal_id)->get();
    }
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
  private function getMalList($checkCredentials = false) {
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

    // Srape page for anime list
    $results = collect(Helpers::scrape_page(str_get_between($page, '</tbody>', '</table>'), '</td>', [
      'status' => [true, '<td class="data status ', '">'],
      'partialUrl' => [false, '<a class="link sort" href="', '</a>'],
      'progress' => [false, '<div class="progress', '</div>'],
    ]));

    // Convert the results to more convenient objects
    $mal_shows = new Collection();
    foreach ($results as $result) {
      $mal_show = new \stdClass();
      $mal_show->status = $result['status'];

      $mal_show->mal_id = str_get_between($result['partialUrl'], '/anime/', '/');
      $mal_show->title = str_get_between($result['partialUrl'], '">');

      $eps_watched = str_get_between($result['progress'], '<a href="javascript: void(0);" class="link edit-disabled">', '</a>');
      if ($eps_watched !== false) {
        $mal_show->eps_watched = $eps_watched;
      } else {
        $mal_show->eps_watched = str_get_between($result['progress'], '<span>', '</span>', true);
      }

      $mal_shows[] = $mal_show;
    }

    return $mal_shows;
  }

  /**
   * Send a post request to the MAL api with the requested data.
   *
   * @return string
   */
  private function postToMal($task, $id, $data = []) {
    if ($task === 'validate') {
      $url = 'https://myanimelist.net/api/account/verify_credentials.xml';
    } else {
      $url = 'https://myanimelist.net/api/animelist/'.$task.'/'.$id.'.xml';
    }

    $dataString = '<?xml version="1.0" encoding="UTF-8"?><entry>';
    foreach ($data as $key => $value) {
      $dataString .= '<'.$key.'>'.$value.'</'.$key.'>';
    }
    $dataString .= '</entry>';

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_USERNAME, $this->mal_user);
    curl_setopt($curl, CURLOPT_PASSWORD, $this->mal_pass);

    if ($task !== 'validate') {
      curl_setopt($curl, CURLOPT_POST, 1);
      curl_setopt($curl, CURLOPT_POSTFIELDS, 'data='.$dataString);
    }

    $response = curl_exec($curl);
    curl_close($curl);

    if ($response === 'Invalid credentials') {
      if ($this->mal_canwrite) {
        $this->mal_canwrite = false;
        $this->save();
      }
      return false;
    } else {
      if (!$this->mal_canwrite) {
        $this->mal_canwrite = true;
        $this->save();
      }
      return $response;
    }
  }

  /**
   * Change the state of the requested show for this user.
   */
  public function changeShowState($mal_show, $status) {
    $this->postToMal('update', $mal_show->mal_id, ['status' => $status]);
    $this->mal_list = $this->mal_list_min->each(function ($item, $key) use ($mal_show, $status) {
      if ($item->mal_id === $mal_show->mal_id) {
        $item->status = $status;
        return false;
      }
    });
    $this->save();
  }

  /**
   * Change the amount of watched episodes for the requested show for this user.
   */
  public function changeShowEpsWatched($mal_show, $eps_watched) {
    $this->postToMal('update', $mal_show->mal_id, ['episode' => $eps_watched]);
    $this->mal_list = $this->mal_list_min->each(function ($item, $key) use ($mal_show, $eps_watched) {
      if ($item->mal_id === $mal_show->mal_id) {
        $item->eps_watched = $eps_watched;
        return false;
      }
    });
    $this->save();
  }

  /**
   * Update the MAL caches and send notifications.
   */
  public function periodicTasks() {
    // Update the MAL cache
    $this->updateCache();

    // Mark plan to watch shows as watching
    if ($this->auto_watching && $this->mal_canwrite) {
      // For all shows on the user's mal list
      foreach ($this->mal_list as $mal_show) {
        // If the current state is 'plan to watch' and we have the show
        // and the show is currently airing for the desired tranlation type
        // and the show state has not been changed before
        if (
          $mal_show->status === 'plantowatch' && isset($mal_show->show) &&
          (
            (isset($mal_show->show->latest_sub) && $this->nots_mail_wants_ttype($mal_show, 'sub') &&
            (!isset($mal_show->show->episode_amount) || $mal_show->show->latest_sub->episode_num < $mal_show->show->episode_amount)) ||
            (isset($mal_show->show->latest_dub) && $this->nots_mail_wants_ttype($mal_show, 'dub') &&
            (!isset($mal_show->show->episode_amount) || $mal_show->show->latest_dub->episode_num < $mal_show->show->episode_amount))
          ) &&
          $this->auto_watching_changed->get($mal_show->mal_id) !== true
        ) {
          $this->auto_watching_changed = $this->auto_watching_changed->put($mal_show->mal_id, true);
          $this->save();
          $this->changeShowState($mal_show, 'watching');
        }
      }
    }

    // Send mail notifications
    if ($this->nots_mail_state) {
      // For all shows on the user's mal list
      foreach ($this->mal_list as $mal_show) {
        // If the user want to recieve notifications for this show and we have the show
        if ($this->nots_mail_state_for($mal_show) && isset($mal_show->show)) {
          // If the user wants subbed notifications for this show and there is a newer sub available and we did not already send a mail for this episode
          if (
            isset($mal_show->show->latest_sub) &&
            $this->nots_mail_wants_ttype($mal_show, 'sub') &&
            $mal_show->eps_watched < $mal_show->show->latest_sub->episode_num &&
            ($this->nots_mail_notified->get($mal_show->mal_id.'_sub') === null ||
            $this->nots_mail_notified->get($mal_show->mal_id.'_sub') < $mal_show->show->latest_sub->episode_num)
          ) {
            // Add this episode to the notified list
            $this->nots_mail_notified = $this->nots_mail_notified->put($mal_show->mal_id.'_sub', $mal_show->show->latest_sub->episode_num);
            $this->save();
            // Send a notification mail (TODO)
            \Mail::send('emails.reports.general', ['description' => 'New Episode Available', 'vars' => [
              'Show Title' => $mal_show->show->title,
              'Latest Sub' => $mal_show->show->latest_sub->episode_num,
            ]], function ($m) use ($mal_show) {
              $m->subject('New episode of anime \''.$mal_show->show->title.'\' (Sub) available');
              $m->from('notifications.animesentinel@wilcodeboer.me', 'AnimeSentinel Notifications');
              $m->to($this->email);
            });
          }
          // If the user wants dubbed notifications for this show and there is a newer dub available and we did not already send a mail for this episode
          if (
            isset($mal_show->show->latest_dub) &&
            $this->nots_mail_wants_ttype($mal_show, 'dub') &&
            $mal_show->eps_watched < $mal_show->show->latest_dub->episode_num &&
            ($this->nots_mail_notified->get($mal_show->mal_id.'_dub') === null ||
            $this->nots_mail_notified->get($mal_show->mal_id.'_dub') < $mal_show->show->latest_dub->episode_num)
          ) {
            // Add this episode to the notified list
            $this->nots_mail_notified = $this->nots_mail_notified->put($mal_show->mal_id.'_dub', $mal_show->show->latest_dub->episode_num);
            $this->save();
            // Send a notification mail (TODO)
            \Mail::send('emails.reports.general', ['description' => 'New Episode Available', 'vars' => [
              'Show Title' => $mal_show->show->title,
              'Latest Dub' => $mal_show->show->latest_dub->episode_num,
            ]], function ($m) use ($mal_show) {
              $m->subject('New episode of anime \''.$mal_show->show->title.'\' (Dub) available');
              $m->from('notifications.animesentinel@wilcodeboer.me', 'AnimeSentinel Notifications');
              $m->to($this->email);
            });
          }
        }
      }
    }
  }
}
