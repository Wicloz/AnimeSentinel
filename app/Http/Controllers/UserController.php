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
  public function overview(Request $request) {
    // Set status to watching if none is requested
    if (!isset($request->status)) {
      $request->status = 'watching';
    }

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
      if ($request->status === 'all' || $request->status === $malField->mal_show->status) {
        $shows[$malField->mal_show->status][] = $malField->toShow();
      }
    }

    // Sort and flatten the shows
    foreach ($shows as $status => $items) {
      $shows[$status] = $items->sortBy('title');
    }
    $shows = $shows->flatten();

    // Expand mal shows which are in our database
    $dbShows = Show::whereIn('mal_id', $shows->pluck('mal_id'))->get();
    foreach ($shows as $index => $show) {
      if (!empty($dbShows->where('mal_id', $show->mal_id)->first())) {
        $shows[$index] = $dbShows->where('mal_id', $show->mal_id)->first();
        $shows[$index]->mal_show = $show->mal_show;
      }
    }

    // Return the view
    return view('users.overview', [
      'shows' => $shows,
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
