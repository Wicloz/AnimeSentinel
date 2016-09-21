<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;

use App\Show;
use App\AnimeSentinel\Actions\ShowManager;
use Illuminate\Support\Facades\Auth;

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
          'show' => $show,
          'mal_show' => Auth::check() ? Auth::user()->mal_show($show->mal_id) : null,
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

    $show = ShowManager::addShowWithMalId($request->mal_id);

    if ($request->gotodetails) {
      return redirect($show->details_url);
    } else {
      return back();
    }
  }
}
