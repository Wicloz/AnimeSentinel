<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;

use App\Show;
use App\MalcacheSearch;
use App\Video;
use App\Streamer;

class AnimeController extends Controller
{
  private $allowedDisplays = ['smallrow', 'bigrow', 'table'];
  private $checkboxes = [
    'types' => [
      'tv', 'ona', 'ova', 'movie', 'special', 'unknown',
    ],
    'genres' => [
      'Action', 'Adventure', 'Cars', 'Comedy', 'Dementia', 'Demons', 'Drama', 'Ecchi',
      'Fantasy', 'Game', 'Harem', 'Historical', 'Horror', 'Josei', 'Kids',
      'Magic', 'Martial Arts', 'Mecha', 'Military', 'Music', 'Mystery', 'Parody', 'Police',
      'Psychological', 'Romance', 'Samurai', 'School', 'Sci-Fi', 'Seinen', 'Shoujo', 'Shoujo Ai',
      'Shounen', 'Shounen Ai', 'Slice of Life', 'Space', 'Sports', 'Super Power', 'Supernatural', 'Thriller',
      'Vampire', 'Yaoi', 'Yuri', 'Unknown',
    ],
    'ratings' => [
      'G', 'PG', 'PG-13', 'R', 'R+', 'Unknown',
    ],
  ];

  /**
   * Instantiate a new controller instance.
   *
   * @return void
   */
  public function __construct() {
    $this->checkboxes['streamers'] = Streamer::pluck('id')->all();
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
   * Set a cookie for the selected translation types for the recently uploaded page.
   *
   * @return \Illuminate\Http\Response
   */
  public function setTtype(Request $request) {
    $ttypes = [];
    if ($request->ttype_sub === 'on') {
      $ttypes[] = 'sub';
    }
    if ($request->ttype_dub === 'on') {
      $ttypes[] = 'dub';
    }
    return back()->withCookie(cookie()->forever('options_recent_ttype', json_encode($ttypes)));
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
    return back()->withCookie(cookie()->forever('options_recent_distinct', $request->distinct));
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
      'checkboxes' => $this->checkboxes,
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

    $request->distincts = collect([
      'show_id' => ['episode_num' => 'desc'],
      'translation_type' => ['episode_num' => 'desc'],
      'episode_num' => [],
      'streamer_id' => [],
    ]);
    $distinct = $request->cookie('options_recent_distinct') !== null ? $request->cookie('options_recent_distinct') : 'episode_num';
    if (!$request->distincts->has($distinct)) {
      $distinct = 'episode_num';
    }
    $request->distincts = $request->distincts->slice(0, $request->distincts->keys()->search($distinct) + 1);

    if ($request->cookie('options_recent_ttype') !== null) {
      $request->ttypes = collect(json_decode($request->cookie('options_recent_ttype')));
    } else {
      $request->ttypes = collect([
        'sub', 'dub',
      ]);
    }

    foreach ($this->checkboxes as $checkbox => $values) {
      $request->$checkbox = collect($values);
      $request->$checkbox = $request->$checkbox->filter(function ($value, $key) use ($checkbox, $request) {
        return $request->{str_replace_last('s', '', $checkbox).'_'.str_replace(' ', '_', $value)} !== 'off';
      });
    }
  }

  /**
   * Find and show anime.
   *
   * @return \Illuminate\Http\Response
   */
  public function search(Request $request) {
    $results = collect([]); $shows = collect([]);
    $this->processRequest($request);

    if ($request->source === 'mal') {
      $shows = MalcacheSearch::search($request->search, $request->types, $request->genres, $request->ratings, $request->pageZ * 50, 51);
    }

    elseif ($request->source === 'as' || $request->search === '') {
      $shows = Show::search($request->search, $request->types, $request->genres, $request->ratings, $request->pageZ * 50, 51, true);
    }

    else {
      $dbShows = Show::search($request->search, $request->types, $request->genres, $request->ratings);
      $malShows = MalcacheSearch::search($request->search, $request->types, $request->genres, $request->ratings);
      $malIds = $dbShows->pluck('mal_id');

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
    $results = collect([]);
    $this->processRequest($request);

    $query = Video::where(function ($query) {
                      $query->where('encoding', '!=', 'embed')->orWhere('encoding', null);
                    })
                  ->whereIn('streamer_id', $request->streamers)
                  ->whereIn('translation_type', $request->ttypes);

    if (count($request->all()) > 0) {
      $shows = Show::search($request->search, $request->types, $request->genres, $request->ratings);
      $query->whereIn('show_id', $shows->pluck('id'));
    }

    $recents = $query->distinctOn($request->distincts->keys(), array_merge($request->distincts->last(), ['uploadtime' => 'asc', 'id' => 'asc']))
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
