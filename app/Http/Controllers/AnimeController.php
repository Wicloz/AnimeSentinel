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
    $this->validate($request, [
      'distinct' => ['required'],
    ]);
    return back()->withCookie(cookie()->forever('distinct_recent', $request->distinct));
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
      'distinct' => $request->distincts[count($request->distincts) - 1],
    ]);
  }

  private function processRequest(Request & $request) {
    $request->search = trim(mb_strtolower($request->q));
    $this->validate($request, [
      'search' => ['min:1', 'max:255'],
      'page' => ['integer', 'between:1,max'],
    ], [], [
      'search' => 'query',
    ]);

    if ($request->page === null) {
      $request->page = 1;
    }
    $request->pageZ = $request->page - 1;

    // Specific to recent page
    $distinct = $request->cookie('distinct_recent') !== null ? $request->cookie('distinct_recent') : 'episode_num';
    $request->distincts = collect([
      'show_id', 'translation_type', 'episode_num', 'streamer_id', 'mirror',
    ]);
    $request->distincts = $request->distincts->slice(0, $request->distincts->flip()->get($distinct) + 1)->all();

    $request->streamers = collect([
      'animeshow', 'kissanime',
    ]);
    $request->streamers = $request->streamers->filter(function ($value, $key) use ($request) {
      $streamer = 'streamer_'.$value;
      return $request->$streamer !== 'off';
    });

    $request->ttypes = collect([
      'sub', 'dub',
    ]);
    $request->ttypes = $request->ttypes->filter(function ($value, $key) use ($request) {
      $ttype = 'ttype_'.$value;
      return $request->$ttype !== 'off';
    });

    // General searching
    $request->types = collect([
      'tv', 'ona', 'ova', 'movie', 'special',
    ]);
    $request->types = $request->types->filter(function ($value, $key) use ($request) {
      $type = 'type_'.$value;
      return $request->$type !== 'off';
    });
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
      $shows = MalcacheSearch::search($request->search, $request->pageZ * 50, 51);
    }

    elseif ($request->source === 'as' || $request->search === '') {
      $shows = Show::search($request->search, $request->types, $request->pageZ * 50, 51, true);
    }

    else {
      $dbShows = Show::search($request->search, $request->types);
      $malIds = $dbShows->pluck('mal_id');
      $malShows = MalcacheSearch::search($request->search);

      $shows = $dbShows->slice($request->pageZ * 50, 51);

      $malShown = $request->pageZ * 50 - $dbShows->count();
      $index = 0;
      foreach ($malShows as $malShow) {
        if (count($shows) < 51 && !$malIds->contains($malShow->mal_id)) {
          $index++;
          if ($index > $malShown) {
            $shows->push($malShow);
          }
        }
      }
    }

    // Determine whether there is a next page
    if (count($shows) > 50) {
      $shows->pop();
      $request->nextPage = true;
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
                    ->whereIn('translation_type', $request->ttypes)
                    ->distinctOn($request->distincts, 'uploadtime')
                    ->orderBy('uploadtime', 'desc')->orderBy('id', 'desc')
                    ->skip($request->pageZ * 50)->take(51)->with('show')->with('streamer')->get();

    // Determine whether there is a next page
    if (count($recents) > 50) {
      $recents->pop();
      $request->nextPage = true;
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
