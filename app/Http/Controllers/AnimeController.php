<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;

use App\Show;
use App\MalcacheSearch;
use App\Video;

class AnimeController extends Controller
{
  private function searchShows($query, $source) {
    if ($source === 'mal') {
      $results = MalcacheSearch::search($query);
    }

    elseif ($source === 'as') {
      $results = Show::search($query);
    }

    else {
      $results = Show::search($query, false);
      $resultsMal = MalcacheSearch::search($query);
      $mal_ids = $results->pluck('mal_id')->all();
      foreach ($resultsMal as $resultMal) {
        if (!in_array($resultMal->mal_id, $mal_ids)) {
          $results->push($resultMal);
        }
      }
    }

    // Expand MAL results which are in our database
    foreach ($results as $index => $result) {
      if (!empty($result->mal)) {
        $show = Show::where('mal_id', $result->mal_id)->first();
        if (!empty($show)) {
          $results[$index] = $show;
        }
      }
    }

    return $results;
  }

  /**
   * Show the home page.
   *
   * @return \Illuminate\Http\Response
   */
  public function home() {
    return view('home', [
      'random' => Show::random()->first(),
      'recent' => [],
    ]);
  }

  /**
   * Show the browse page with the requested results.
   *
   * @return \Illuminate\Http\Response
   */
  public function browse(Request $request, $results) {
    return view('anime.browse', [
      'results' => $results,
      'display' => 'bigrow',
    ]);
  }

  /**
   * Find and show anime.
   *
   * @return \Illuminate\Http\Response
   */
  public function search(Request $request) {
    $results = []; $resultsNested = [];

    if (!empty($request->q)) {
      $this->validate($request, [
        'q' => ['min:1', 'max:255']
      ], [], [
        'q' => 'query'
      ]);
      $query = trim(mb_strtolower($request->q));
      $results = $this->searchShows($query, $request->source);
    }

    foreach ($results as $result) {
      $resultsNested[] = [
        'isMal' => !empty($result->mal),
        'show' => $result,
        'video' => null,
      ];
    }

    return $this->browse($request, $resultsNested);
  }

  /**
   * Find and show recent videos.
   *
   * @return \Illuminate\Http\Response
   */
  public function recent(Request $request) {
    $resultsNested = [];

    if (!empty($request->q)) {
      $this->validate($request, [
        'q' => ['min:1', 'max:255']
      ], [], [
        'q' => 'query'
      ]);
      $query = trim(mb_strtolower($request->q));
      $results = Show::search($query);

      $recents = Video::whereIn('show_id', $results->pluck('id'))
                 ->where('encoding', '!=', 'embed')->orWhere('encoding', null)
                 ->distinctOn(['show_id', 'translation_type', 'episode_num', 'streamer_id'], 'uploadtime')
                 ->orderBy('uploadtime', 'desc')->orderBy('id', 'desc')
                 ->take(256)->with('show')->with('streamer')->get();
    }

    else {
      $recents = Video::where('encoding', '!=', 'embed')->orWhere('encoding', null)
                 ->distinctOn(['show_id', 'translation_type', 'episode_num', 'streamer_id'], 'uploadtime')
                 ->orderBy('uploadtime', 'desc')->orderBy('id', 'desc')
                 ->take(256)->with('show')->with('streamer')->get();
    }

    foreach ($recents as $recent) {
      $resultsNested[] = [
        'isMal' => false,
        'show' => $recent->show,
        'video' => $recent,
      ];
    }

    return $this->browse($request, $resultsNested);
  }
}
