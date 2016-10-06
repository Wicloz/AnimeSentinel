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
    'username', 'email', 'password', 'viewsettings_overview', 'mal_user', 'mal_pass', 'mal_canread', 'mal_canwrite', 'nots_mail_state', 'nots_mail_settings', 'auto_watching_state',
  ];

  /**
   * The attributes that should be casted to native types.
   *
   * @var array
   */
  protected $casts = [
    'viewsettings_overview' => 'collection',
    'nots_mail_settings' => 'collection',
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
  * Get all mal fields for this user.
  *
  * @return \Illuminate\Database\Eloquent\Relations\Relation
  */
  public function malFields() {
    return $this->hasMany(MaluserField::class);
  }

  /**
  * Get all shows for this user.
  *
  * @return \Illuminate\Database\Eloquent\Collection
  */
  public function getShowsAttribute() {
    return Show::whereIn('mal_id', $this->malFields()->pluck('mal_id'))->get();
  }

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
  * Get the viewsettings for the overview page with missing values set to defaults.
  *
  * @return \Illuminate\Database\Eloquent\Collection
  */
  public function getViewsettingsOverviewAttribute($value) {
    $settings = collect(json_decode($value));

    if (!$settings->has('states')) {
      $settings['states'] = ['watching'];
    }

    if (!$settings->has('thumbnails')) {
      $settings['thumbnails'] = true;
    }

    if (!$settings->has('cutoff')) {
      $settings['cutoff'] = 3;
    } else {
      $settings['cutoff'] = (int) $settings['cutoff'];
    }

    return $settings;
  }


  /**
   * Update this user's cached MAL list and credential status.
   *
   * @return boolean
   */
  public function updateMalCache() {
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
    // Check write permissions
    $this->postToMal('validate', 0);

    // Srape page for anime list
    $results = collect(Helpers::scrape_page(str_get_between($page, '</tbody>', '</table>'), '</td>', [
      'status' => [true, '<td class="data status ', '">'],
      'thumbnail_id' => [false, '/images/anime', '?'],
      'partialUrl' => [false, '<a class="link sort" href="', '</a>'],
      'progress' => [false, '<div class="progress', '</div>'],
    ]));

    // Convert the results to more convenient objects and save them
    foreach ($results as $result) {
      $mal_show = new \stdClass();
      $mal_show->status = $result['status'];

      $mal_show->mal_id = str_get_between($result['partialUrl'], '/anime/', '/');
      $mal_show->title = str_get_between($result['partialUrl'], '">');

      $mal_show->thumbnail_id = $result['thumbnail_id'];

      $eps_watched = str_get_between($result['progress'], '<a href="javascript: void(0);" class="link edit-disabled">', '</a>');
      if ($eps_watched !== false) {
        $mal_show->eps_watched = $eps_watched;
      } else {
        $mal_show->eps_watched = str_get_between($result['progress'], '<span>', '</span>', true);
      }
      if ($mal_show->eps_watched === '-') {
        $mal_show->eps_watched = 0;
      }

      // Save or update the mal field
      $mal_field = $this->malFields()->firstOrNew(['mal_id' => $mal_show->mal_id]);
      $mal_field->mal_show = $mal_show;
      $mal_field->save();
    }
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
  public function changeShowState($mal_id, $status) {
    $mal_field = $this->malFields()->where('mal_id', $mal_id)->first();
    if ($mal_field->mal_show->status != $status) {
      $this->postToMal('update', $mal_id, ['status' => $status]);
      $temp = $mal_field->mal_show;
      $temp->status = $status;
      $mal_field->mal_show = $temp;
      $mal_field->save();
    }
  }

  /**
   * Change the amount of watched episodes for the requested show for this user.
   */
  public function changeShowEpsWatched($mal_id, $eps_watched) {
    $mal_field = $this->malFields()->where('mal_id', $mal_id)->first();
    if ($mal_field->mal_show->eps_watched != $eps_watched) {
      $this->postToMal('update', $mal_id, ['episode' => $eps_watched]);
      $temp = $mal_field->mal_show;
      $temp->eps_watched = $eps_watched;
      $mal_field->mal_show = $temp;
      $mal_field->save();
    }
  }

  /**
   * Update the MAL caches and send notifications.
   */
  public function periodicTasks() {
    if ($this->mal_user === '') {
      return;
    }

    // Update the MAL cache
    $this->updateMalCache();

    // Mark plan to watch shows as watching
    if ($this->auto_watching_state && $this->mal_canwrite) {
      // For all shows on the user's mal list
      foreach ($this->malFields->load('show', 'user') as $mal_field) {
        // If we have the show and the show state has not been changed before
        // and the show is currently airing for the desired tranlation type
        if (
          isset($mal_field->show) && !$mal_field->auto_watching_changed && (
            $mal_field->notsMailWantsForType('sub') && $mal_field->show->isAiring('sub') ||
            $mal_field->notsMailWantsForType('dub') && $mal_field->show->isAiring('dub')
          )
        ) {
          $mal_field->auto_watching_changed = true;
          $mal_field->save();
          $this->changeShowState($mal_field->mal_show->mal_id, 'watching');
        }
      }
    }

    // Send mail notifications
    if ($this->nots_mail_state && $this->mal_canread) {
      // For all shows on the user's mal list
      foreach ($this->malFields->load('show', 'user') as $mal_field) {
        // If the user wants subbed notifications for this show and we have at least one episode
        if ($mal_field->notsMailWantsForType('sub') && isset($mal_field->show->latest_sub)) {
          $episodeNums_now = $mal_field->show->episodes('sub')->pluck('episode_num');
          // Mark all episodes as notified if this is the first check
          if ($mal_field->nots_mail_notified_sub === null) {
            $mal_field->nots_mail_notified_sub = $episodeNums_now;
            $mal_field->save();
          }
          $episodeNums_diff = $episodeNums_now->diff($mal_field->nots_mail_notified_sub);
          // If there are episodes for which the notification email has not been sent
          if (count($episodeNums_diff) > 0) {
            // Update the notified list
            $mal_field->nots_mail_notified_sub = $episodeNums_now;
            $mal_field->save();
            // If the newest episode is higher than the amount of episodes watched
            if ($episodeNums_diff->max() > $mal_field->mal_show->eps_watched) {
              // Send a notification mail (TODO)
              \Mail::send('emails.reports.general', ['description' => 'New Episode Available', 'vars' => [
                'Show Title' => $mal_field->show->title,
                'Latest Sub' => $mal_field->show->videos()->episode('sub', $episodeNums_diff->max())->first()->episode_num,
              ]], function ($m) use ($mal_field) {
                $m->subject('New episode of anime \''.$mal_field->show->title.'\' (Sub) available');
                $m->from('notifications@animesentinel.tv', 'AnimeSentinel Notifications');
                $m->to($this->email);
              });
            }
          }
        }
        // If the user wants dubbed notifications for this show and we have at least one episode
        if ($mal_field->notsMailWantsForType('dub') && isset($mal_field->show->latest_dub)) {
          $episodeNums_now = $mal_field->show->episodes('dub')->pluck('episode_num');
          // Mark all episodes as notified if this is the first check
          if ($mal_field->nots_mail_notified_dub === null) {
            $mal_field->nots_mail_notified_dub = $episodeNums_now;
            $mal_field->save();
          }
          $episodeNums_diff = $episodeNums_now->diff($mal_field->nots_mail_notified_dub);
          // If there are episodes for which the notification email has not been sent
          if (count($episodeNums_diff) > 0) {
            // Update the notified list
            $mal_field->nots_mail_notified_dub = $episodeNums_now;
            $mal_field->save();
            // If the newest episode is higher than the amount of episodes watched
            if ($episodeNums_diff->max() > $mal_field->mal_show->eps_watched) {
              // Send a notification mail (TODO)
              \Mail::send('emails.reports.general', ['description' => 'New Episode Available', 'vars' => [
                'Show Title' => $mal_field->show->title,
                'Latest Dub' => $mal_field->show->videos()->episode('dub', $episodeNums_diff->max())->first()->episode_num,
              ]], function ($m) use ($mal_field) {
                $m->subject('New episode of anime \''.$mal_field->show->title.'\' (Dub) available');
                $m->from('notifications@animesentinel.tv', 'AnimeSentinel Notifications');
                $m->to($this->email);
              });
            }
          }
        }
      }
    }
  }
}
