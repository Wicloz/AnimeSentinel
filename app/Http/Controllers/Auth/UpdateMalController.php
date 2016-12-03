<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests;

use Illuminate\Support\Facades\Auth;
use App\Show;
use App\AnimeSentinel\ShowManager;

class UpdateMalController extends Controller
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
   * Add the requested anime to the user's list.
   *
   * @return \Illuminate\Http\Response
   */
  public function add(Request $request) {
    $this->validate($request, [
      'show_id' => ['required', 'integer', 'between:0,max'],
      'status' => ['required', 'in:watching,completed,onhold,dropped,plantowatch'],
      'eps_watched' => ['required', 'integer', 'between:0,2000'],
      'score' => ['required', 'integer', 'between:0,10'],
    ]);

    $show = Show::find($request->show_id);
    if (isset($show)) {
      if (!isset($show->mal_id)) {
        flash_error('The requested anime could not be found on MAL.');
      } elseif (!$show->mal_linked) {
        ShowManager::updateShowCache($show->id);
        return $this->add($request);
      } else {
        Auth::user()->addAnime($show->mal_id, $request->status, $request->eps_watched, $request->score);
      }
    } else {
      flash_error('The requested anime could not be found.');
    }

    return back();
  }

  /**
   * Update all data for the requested mal show for this user.
   *
   * @return \Illuminate\Http\Response
   */
  public function full(Request $request) {
    $this->epsWatched($request);
    $this->status($request);
    $this->score($request);
    return back();
  }

  /**
   * Update the status of the requested mal show for this user.
   *
   * @return \Illuminate\Http\Response
   */
  public function status(Request $request) {
    $this->validate($request, [
      'show_id' => ['required', 'integer', 'between:0,max'],
      'status' => ['required', 'in:watching,completed,onhold,dropped,plantowatch'],
    ]);

    $show = Show::find($request->show_id);
    if (isset($show)) {
      Auth::user()->changeShowStatus($show->mal_id, $request->status);
    } else {
      flash_error('The requested anime could not be found.');
    }

    return back();
  }

  /**
   * Update the amount of episodes watched of the requested mal show for this user.
   *
   * @return \Illuminate\Http\Response
   */
  public function epsWatched(Request $request) {
    $this->validate($request, [
      'show_id' => ['required', 'integer', 'between:0,max'],
      'eps_watched' => ['required', 'integer', 'between:0,2000'],
    ]);

    $show = Show::find($request->show_id);
    if (isset($show)) {
      Auth::user()->changeShowEpsWatched($show->mal_id, $request->eps_watched);
    } else {
      flash_error('The requested anime could not be found.');
    }

    return back();
  }

  /**
   * Update the score of the requested mal show for this user.
   *
   * @return \Illuminate\Http\Response
   */
  public function score(Request $request) {
    $this->validate($request, [
      'show_id' => ['required', 'integer', 'between:0,max'],
      'score' => ['required', 'integer', 'between:0,10'],
    ]);

    $show = Show::find($request->show_id);
    if (isset($show)) {
      Auth::user()->changeShowScore($show->mal_id, $request->score);
    } else {
      flash_error('The requested anime could not be found.');
    }

    return back();
  }
}
