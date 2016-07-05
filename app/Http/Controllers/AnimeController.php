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
    // TODO
    $results = Show::all();

    return view('anime.search', [
      'results' => $results
    ]);
  }

  /**
   * Show the 'recently uploaded' page.
   *
   * @return \Illuminate\Http\Response
   */
  public function recent() {
    // TODO
    $shows = Show::all();

    return view('anime.recent', [
      'shows' => $shows
    ]);
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

  /**
   * Show all available video's for a certain anime episode.
   *
   * @return \Illuminate\Http\Response
   */
  public function episode(Show $show, $anime_type, $episode_num) {
    return view('anime.episode', [
      'show' => $show,
      'type' => $anime_type,
      'number' => $episode_num
    ]);
  }
}
