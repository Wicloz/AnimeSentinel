<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;

use App\Show;
use App\MalcacheSearch;
use App\Video;

class AnimeController extends Controller
{
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
  public function browse(Request $request, $mode, $results) {
    return view('anime.'.$mode, [
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
    $results = []; $shows = [];

    if (!empty($request->q)) {
      $this->validate($request, [
        'q' => ['min:1', 'max:255']
      ], [], [
        'q' => 'query'
      ]);
      $query = trim(mb_strtolower($request->q));

      if ($request->source === 'mal') {
        $shows = MalcacheSearch::search($query);
      }

      elseif ($request->source === 'as') {
        $shows = Show::search($query, true);
      }

      else {
        $shows = Show::search($query);
        $showsMal = MalcacheSearch::search($query);
        $mal_ids = $shows->pluck('mal_id');
        foreach ($showsMal as $showMal) {
          if (!$mal_ids->contains($showMal->mal_id)) {
            $shows->push($showMal);
          }
        }
      }

      // Expand MAL results which are in our database
      foreach ($shows as $index => $show) {
        if (!empty($show->mal)) {
          $show = Show::where('mal_id', $show->mal_id)->first();
          if (!empty($show)) {
            $shows[$index] = $show;
          }
        }
      }
    }

    foreach ($shows as $show) {
      $results[] = [
        'isMal' => !empty($show->mal),
        'show' => $show,
        'video' => null,
      ];
    }

    return $this->browse($request, 'search', $results);
  }

  /**
   * Find and show recent videos.
   *
   * @return \Illuminate\Http\Response
   */
  public function recent(Request $request) {
    $results = [];
    $getRecent = Video::where('encoding', '!=', 'embed')->orWhere('encoding', null)
                      ->distinctOn(['show_id', 'translation_type', 'episode_num', 'streamer_id'], 'uploadtime')
                      ->orderBy('uploadtime', 'desc')->orderBy('id', 'desc')
                      ->take(128)->with('show')->with('streamer');

    if (!empty($request->q)) {
      $this->validate($request, [
        'q' => ['min:1', 'max:255']
      ], [], [
        'q' => 'query'
      ]);
      $query = trim(mb_strtolower($request->q));
      $results = Show::search($query);

      $recents = $getRecent->whereIn('show_id', $results->pluck('id'))->get();
    }

    else {
      $recents = $getRecent->get();
    }

    foreach ($recents as $recent) {
      $results[] = [
        'isMal' => false,
        'show' => $recent->show,
        'video' => $recent,
      ];
    }

    return $this->browse($request, 'recent', $results);
  }
}
