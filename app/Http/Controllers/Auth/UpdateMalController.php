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
   * Update the requested mal show for this user.
   *
   * @return \Illuminate\Http\Response
   */
  public function update(Request $request) {
    $this->validate($request, [
      'mal_id' => ['required', 'integer', 'between:0,max'],
      'eps_watched' => ['required', 'integer', 'between:0,max'],
      'status' => ['required', 'in:watching,completed,onhold,dropped,plantowatch'],
      'score' => ['required', 'integer', 'between:0,10'],
    ]);

    Auth::user()->changeShowEpsWatched($request->mal_id, $request->eps_watched);
    Auth::user()->changeShowState($request->mal_id, $request->status);
    Auth::user()->changeShowScore($request->mal_id, $request->score);
    return back();
  }
}
