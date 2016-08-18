<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;

use App\Show;
use App\AnimeSentinel\ShowManager;

class ShowController extends Controller
{
  /**
   * Show the details page for a show.
   *
   * @return \Illuminate\Http\Response
   */
  public function details(Show $show) {
    if (!visitPage('show_'.$show->id)) {
      $show->hits++;
      $show->save();
    }
    return view('anime.details', [
      'show' => $show
    ]);
  }

  /**
   * Attempt to add the requested show to the database.
   *
   * @return \Illuminate\Http\Response
   */
  public function insert(Request $request) {
    $this->validate($request, [
      'mal_id' => ['required']
    ]);

    $show = ShowManager::addShowWithMalId($request->mal_id);

    if ($request->gotodetails) {
      return redirect($show->details_url);
    } else {
      return back();
    }
  }
}
