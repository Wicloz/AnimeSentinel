<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;

use App\Show;
use App\MalcacheSearch;
use App\Video;

class AnimeController extends Controller
{
  private $allowedDisplays = ['smallrow', 'bigrow'];

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
   * Set a cookie for the display to use for lists on the requested page.
   *
   * @return \Illuminate\Http\Response
   */
  public function setDisplay(Request $request) {
    $this->validate($request, [
      'display' => ['required'],
      'page' => ['required'],
    ]);
    return back()->withCookie(cookie()->forever('display_'.$request->page, $request->display));
  }

  /**
   * Set a cookie for the distinct on columns to use for the recently uploaded page.
   *
   * @return \Illuminate\Http\Response
   */
  public function setDistinct(Request $request) {
    $distincts = collect([
      'show_id', 'translation_type', 'episode_num', 'streamer_id', 'mirror',
    ]);
    $distincts = $distincts->filter(function ($value, $key) use ($request) {
      $distinct = 'distinct_'.$value;
      return $request->$distinct === 'on';
    });
    return back()->withCookie(cookie()->forever('distincton', json_encode($distincts)));
  }

  /**
   * Show the browse page with the requested results.
   *
   * @return \Illuminate\Http\Response
   */
  public function browse(Request $request, $mode, $results) {
    $display = $request->cookie('display_'.$mode);
    if (!in_array($display, $this->allowedDisplays)) {
      $display = 'smallrow';
    }
    return view('anime.'.$mode, [
      'results' => $results,
      'display' => $display,
      'mode' => $mode,
      'request' => $request,
    ]);
  }

  private function processRequest(Request & $request) {
    $request->search = trim(mb_strtolower($request->q));
    $this->validate($request, [
      'search' => ['min:1', 'max:255'],
    ], [], [
      'search' => 'query',
    ]);

    $request->types = collect([
      'tv', 'ona', 'ova', 'movie', 'special',
    ]);
    $request->types = $request->types->filter(function ($value, $key) use ($request) {
      $type = 'type_'.$value;
      return $request->$type !== 'off';
    });

    $request->streamers = collect([
      'animeshow', 'kissanime',
    ]);
    $request->streamers = $request->streamers->filter(function ($value, $key) use ($request) {
      $streamer = 'streamer_'.$value;
      return $request->$streamer !== 'off';
    });

    $request->distincts = $request->cookie('distincton') !== null ? json_decode($request->cookie('distincton')) : [
      'show_id', 'translation_type', 'episode_num',
    ];
  }

  /**
   * Find and show anime.
   *
   * @return \Illuminate\Http\Response
   */
  public function search(Request $request) {
    $results = []; $shows = [];
    $this->processRequest($request);

    if ($request->source === 'mal') {
      $shows = MalcacheSearch::search($request->search);
    }

    elseif ($request->source === 'as') {
      $shows = Show::search($request->search, $request->types, 0, 50, true);
    }

    else {
      $shows = Show::search($request->search, $request->types, 0, 50);
      $malShows = MalcacheSearch::search($request->search);
      $mal_ids = $shows->pluck('mal_id');
      foreach ($malShows as $malShow) {
        if (!$mal_ids->contains($malShow->mal_id)) {
          $shows->push($malShow);
        }
      }
    }

    // Expand MAL results which are in our database
    $dbShows = Show::whereIn('mal_id', $shows->where('mal', true)->pluck('mal_id'))->get();
    foreach ($shows->where('mal', true) as $index => $show) {
      if (!empty($dbShows->where('mal_id', $show->mal_id)->first())) {
        $shows[$index] = $dbShows->where('mal_id', $show->mal_id)->first();
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
    $this->processRequest($request);

    $shows = Show::search($request->search, $request->types);

    $recents = Video::whereIn('show_id', $shows->pluck('id'))
                    ->whereIn('streamer_id', $request->streamers)
                    ->where(function ($query) {
                        $query->where('encoding', '!=', 'embed')->orWhere('encoding', null);
                      })
                    ->distinctOn($request->distincts, 'uploadtime')
                    ->orderBy('uploadtime', 'desc')->orderBy('id', 'desc')
                    ->skip(0)->take(50)->with('show')->with('streamer')->get();

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
