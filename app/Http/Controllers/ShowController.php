<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Show;
use App\AnimeSentinel\ShowManager;

class ShowController extends Controller
{
  /**
   * Show the details page for a show.
   *
   * @return \Illuminate\Http\Response
   */
  public function details($show_id, $title = '') {
    $show = Show::getShowFromUrl($show_id, $title);

    if (isset($show)) {
      if ($show->id === (int) $show_id && $title === slugify($show->title)) {
        if (!visitPage('show_'.$show->id)) {
          $show->hits++;
          $show->save();
        }
        return view('anime.details', [
          'show' => $show
        ]);
      }
      else {
        return redirect($show->details_url);
      }
    }

    abort(404);
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

    runJob(new \App\Jobs\ShowAddMal($request->mal_id));
    $show = Show::where('mal_id', $request->mal_id)->first();

    if ($request->gotodetails) {
      return redirect($show->details_url);
    } else {
      return back();
    }
  }
}
