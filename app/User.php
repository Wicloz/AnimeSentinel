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
  public function updateMalCache($force = false) {
    // Stop if there is no mal username
    if ($this->mal_user === '') {
      $this->mal_canread = false;
      $this->mal_canwrite = false;
      $this->save();
      return false;
    }

    // Download the page
    $page = Downloaders::downloadPage('http://myanimelist.net/malappinfo.php?u='.$this->mal_user.'&status=all&type=anime');
    // Check whether the page is valid, stop if it isn't
    if (str_contains($page, 'Invalid username')) {
      $this->mal_canread = false;
      $this->save();
      return false;
    } else {
      $this->mal_canread = true;
      $this->save();
    }
    // Check write permissions
    if ($this->mal_canwrite || $force) {
      $this->postToMal('validate', 0);
    }

    // Srape the xml to get the anime list
    $results = collect(Helpers::scrape_page($page, '</anime>', [
      'mal_id' => [true, '<series_animedb_id>', '</series_animedb_id>'],
      'title' => [false, '<series_title>', '</series_title>'],
      'status' => [false, '<my_status>', '</my_status>'],
      'thumbnail_id' => [false, '/images/anime/', '</series_image>'],
      'eps_watched' => [false, '<my_watched_episodes>', '</my_watched_episodes>'],
      'score' => [false, '<my_score>', '</my_score>'],
      'rewatching' => [false, '<my_rewatching>', '</my_rewatching>'],
    ]));

    $malIds_list = [];
    // Convert the results to more convenient objects and save them
    foreach ($results as $result) {
      $mal_show = new \stdClass();

      $mal_show->mal_id = $result['mal_id'];
      $mal_show->title = $result['title'];
      $mal_show->remote_thumbnail_urls = ['https://myanimelist.cdn-dena.com/images/anime/'.trim($result['thumbnail_id'])];
      $mal_show->eps_watched = (int) $result['eps_watched'];
      $mal_show->score = (int) $result['score'];
      $mal_show->rewatching = $result['rewatching'] === '1';

      switch ((int) $result['status']) {
        case 1:
          $mal_show->status = 'watching';
        break;
        case 2:
          $mal_show->status = 'completed';
        break;
        case 3:
          $mal_show->status = 'onhold';
        break;
        case 4:
          $mal_show->status = 'dropped';
        break;
        case 6:
          $mal_show->status = 'plantowatch';
        break;
      }

      $malIds_list[] = $mal_show->mal_id;

      // Insert or update the mal field
      $mal_field = $this->malFields()->firstOrNew(['mal_id' => $mal_show->mal_id]);
      $mal_field->mal_show = $mal_show;
      $mal_field->save();
    }

    // Remove all mal fields no longer on the user's list
    $malIds_db = $this->malFields()->pluck('mal_id');
    $malIds_diff = $malIds_db->diff($malIds_list);
    $this->malFields()->whereIn('mal_id', $malIds_diff)->delete();
  }

  /**
   * Send a post request to the MAL api with the requested data.
   *
   * @return string
   */
  private function postToMal($task, $id, $data = []) {
    if ($task === 'validate' || $this->mal_canwrite) {
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

    else {
      return false;
    }
  }

  /**
   * Add the requested anime to this user's list.
   *
   * @return boolean
   */
  public function addAnime($mal_id, $status = 'watching', $eps_watched = 0, $score = 0) {
    if ($this->malFields()->where('mal_id', $mal_id)->first() === null) {
      $this->postToMal('add', $mal_id, [
        'status' => $status,
        'episode' => $eps_watched,
        'score' => $score,
      ]);
      $this->updateMalCache();
      return true;
    } else {
      flash_error('An anime with that id is already on your list.');
      return false;
    }
  }

  /**
   * Change the state of the requested show for this user.
   *
   * @return boolean
   */
  public function changeShowStatus($mal_id, $status) {
    $mal_field = $this->malFields()->where('mal_id', $mal_id)->first();
    if ($mal_field === null) {
      flash_error('An anime with that id is not on your list.');
      return false;
    }
    elseif ($mal_field->mal_show->status !== $status) {
      $this->postToMal('update', $mal_id, ['status' => $status]);
      $temp = $mal_field->mal_show;
      $temp->status = $status;
      $mal_field->mal_show = $temp;
      $mal_field->save();
    }
    return true;
  }

  /**
   * Change the amount of watched episodes for the requested show for this user.
   *
   * @return boolean
   */
  public function changeShowEpsWatched($mal_id, $eps_watched) {
    $eps_watched = (int) $eps_watched;
    $mal_field = $this->malFields()->where('mal_id', $mal_id)->first();
    if ($mal_field === null) {
      flash_error('An anime with that id is not on your list.');
      return false;
    }
    elseif ($mal_field->mal_show->eps_watched !== $eps_watched) {
      $this->postToMal('update', $mal_id, ['episode' => $eps_watched]);
      $temp = $mal_field->mal_show;
      $temp->eps_watched = $eps_watched;
      $mal_field->mal_show = $temp;
      $mal_field->save();
    }
    return true;
  }

  /**
   * Change the amount of watched episodes for the requested show for this user.
   *
   * @return boolean
   */
  public function changeShowScore($mal_id, $score) {
    $score = (int) $score;
    $mal_field = $this->malFields()->where('mal_id', $mal_id)->first();
    if ($mal_field === null) {
      flash_error('An anime with that id is not on your list.');
      return false;
    }
    elseif ($mal_field->mal_show->score !== $score) {
      $this->postToMal('update', $mal_id, ['score' => $score]);
      $temp = $mal_field->mal_show;
      $temp->score = $score;
      $mal_field->mal_show = $temp;
      $mal_field->save();
    }
    return true;
  }

  /**
   * Update the MAL caches and send notifications.
   */
  public function periodicTasks() {
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
          $this->changeShowStatus($mal_field->mal_show->mal_id, 'watching');
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
                $m->from('notifications.animesentinel@wilcodeboer.me', 'AnimeSentinel Notifications');
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
                $m->from('notifications.animesentinel@wilcodeboer.me', 'AnimeSentinel Notifications');
                $m->to($this->email);
              });
            }
          }
        }
      }
    }
  }
}
