<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Show;
use App\Episode;

class AnimeController extends Controller
{
  /**
   * Show the search page for all shows.
   *
   * @return \Illuminate\Http\Response
   */
  public function search() {
      return view('anime.search');
  }

  /**
   * Show the 'recently uploaded' page.
   *
   * @return \Illuminate\Http\Response
   */
  public function recent() {
      return view('anime.recent');
  }

  /**
   * Show the details page for a show.
   *
   * @return \Illuminate\Http\Response
   */
  public function details(Show $show) {
      return view('anime.details', [
        'show' => $show
      ]);
  }
}
