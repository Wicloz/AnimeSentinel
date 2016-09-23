<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;

use App\AnimeSentinel\Actions\ShowManager;

class PostController extends Controller
{
  /**
   * Attempt to add the requested show to the database.
   *
   * @return \Illuminate\Http\Response
   */
  public function showAdd(Request $request) {
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
