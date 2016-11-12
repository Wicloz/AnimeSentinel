<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;

use Illuminate\Support\Facades\Auth;
use App\Show;

class UserController extends Controller
{
  /**
   * Create a new controller instance.
   *
   * @return void
   */
  public function __construct() {
    $this->middleware('auth');
  }

  /**
   * Show the page containing an overview of the user's anime list.
   *
   * @return \Illuminate\Http\Response
   */
  public function overview() {
    // Create shows per status
    $shows = collect([
      'watching' => collect([]),
      'completed' => collect([]),
      'onhold' => collect([]),
      'dropped' => collect([]),
      'plantowatch' => collect([]),
    ]);

    // Fill all states that are requested
    $malFields = Auth::user()->malFields;
    foreach ($malFields as $malField) {
      if (in_array($malField->mal_show->status, Auth::user()->viewsettings_overview->get('states'))) {
        $shows[$malField->mal_show->status][] = $malField->toShow();
      }
    }

    // Sort and flatten the shows
    foreach ($shows as $status => $items) {
      // Expand mal shows which are in our database
      $dbShows = Show::whereIn('mal_id', $items->pluck('mal_id'))->get();
      foreach ($items as $index => $show) {
        if (!empty($dbShows->where('mal_id', $show->mal_id)->first())) {
          $items[$index] = $dbShows->where('mal_id', $show->mal_id)->first();
          $items[$index]->mal_show = $show->mal_show;
        }
      }

      $shows[$status] = $items->sort(function ($a, $b) {
        // Move shows not in our database to the bottom
        if ($a->mal && $b->mal) {
          return $a->title <=> $b->title;
        } else if ($a->mal) {
          return 1;
        } else if ($b->mal) {
          return -1;
        }

        // Move shows still searching for videos to the top
        if (!$a->videos_initialised && !$b->videos_initialised) {
          return $a->title <=> $b->title;
        } else if (!$a->videos_initialised) {
          return -1;
        } else if (!$b->videos_initialised) {
          return 1;
        }

        // Sort shows by amount of unwatched episodes
        // $epsA = $a->episodes('sub', 'asc', $a->mal_show->eps_watched);
        // $epsB = $b->episodes('sub', 'asc', $b->mal_show->eps_watched);
        // if ($epsA->count() !== $epsB->count()) {
        //   return $epsB->count() - $epsA->count();
        // }

        // Sort shows by next upload estimate, where finished shows are at the bottom
        $nextA = $a->nextUploadEstimate('sub');
        $nextB = $b->nextUploadEstimate('sub');
        if ($nextA === null && $nextB === null) {
          return $a->title <=> $b->title;
        } else if ($nextA === null) {
          return 1;
        } else if ($nextB === null) {
          return -1;
        } else if ($nextA->gt($nextB)) {
          return 1;
        } else if ($nextA->lt($nextB)) {
          return -1;
        }

        return 0;
      });
    }
    $shows = $shows->flatten();

    // Find the columns that need to be shown
    $columns = collect([
      'title',
      'episode_duration',
      'watchable',
      'broadcasts',
    ]);
    if (Auth::user()->viewsettings_overview->get('thumbnails')) {
      $columns->prepend('thumbnail');
    }

    // Return the view
    return view('users.overview', [
      'shows' => $shows,
      'columns' => $columns,
    ]);
  }

  /**
   * Show a settings page for the user's general data.
   *
   * @return \Illuminate\Http\Response
   */
  public function settings_general() {
    return view('users.settings.general');
  }

  /**
   * Show a settings page for the user's mail notification settings.
   *
   * @return \Illuminate\Http\Response
   */
  public function settings_nots_mail(Request $request) {
    return view('users.settings.notifications.mail', [
      'mal_list' => !empty($request->status) ? Auth::user()->malFields()->pluck('mal_show')->where('status', $request->status) : null,
      'loadedStatus' => $request->status,
    ]);
  }
}
