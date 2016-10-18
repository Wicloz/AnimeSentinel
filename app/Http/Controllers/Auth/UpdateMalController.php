<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests;

use Illuminate\Support\Facades\Auth;

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
      'mal_id' => ['required', 'integer', 'between:0,max'],
      'status' => ['required', 'in:watching,completed,onhold,dropped,plantowatch'],
      'eps_watched' => ['required', 'integer', 'between:0,2000'],
      'score' => ['required', 'integer', 'between:0,10'],
    ]);
    Auth::user()->addAnime($request->mal_id, $request->status, $request->eps_watched, $request->score);
    return back();
  }

  /**
   * Update all data for the requested mal show for this user.
   *
   * @return \Illuminate\Http\Response
   */
  public function full(Request $request) {
    $this->epsWatched($request);
    $this->state($request);
    $this->score($request);
    return back();
  }

  /**
   * Update the status of the requested mal show for this user.
   *
   * @return \Illuminate\Http\Response
   */
  public function state(Request $request) {
    $this->validate($request, [
      'mal_id' => ['required', 'integer', 'between:0,max'],
      'status' => ['required', 'in:watching,completed,onhold,dropped,plantowatch'],
    ]);
    Auth::user()->changeShowStatus($request->mal_id, $request->status);
    return back();
  }

  /**
   * Update the amount of episodes watched of the requested mal show for this user.
   *
   * @return \Illuminate\Http\Response
   */
  public function epsWatched(Request $request) {
    $this->validate($request, [
      'mal_id' => ['required', 'integer', 'between:0,max'],
      'eps_watched' => ['required', 'integer', 'between:0,2000'],
    ]);
    Auth::user()->changeShowEpsWatched($request->mal_id, $request->eps_watched);
    return back();
  }

  /**
   * Update the score of the requested mal show for this user.
   *
   * @return \Illuminate\Http\Response
   */
  public function score(Request $request) {
    $this->validate($request, [
      'mal_id' => ['required', 'integer', 'between:0,max'],
      'score' => ['required', 'integer', 'between:0,10'],
    ]);
    Auth::user()->changeShowScore($request->mal_id, $request->score);
    return back();
  }
}
