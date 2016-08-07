<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;

use App\Show;
use App\Video;
use App\AnimeSentinel\MyAnimeList;

class AnimeController extends Controller
{
  protected function recentShows($limit, $noDuplicates) {
    if ($noDuplicates) {
      $recent = Video::orderBy('uploadtime', 'desc')->orderBy('id', 'desc')
                     ->groupBy(['show_id', 'translation_type', 'episode_num'])->distinct()
                     ->take($limit)->with('show')->get();
    }

    else {
      $recent = Video::where('mirror', 1)
                     ->orderBy('uploadtime', 'desc')
                     ->orderBy('id', 'desc')
                     ->take($limit)->with('show')->get();
    }

    return $recent;
  }

  /**
   * Show the home page.
   *
   * @return \Illuminate\Http\Response
   */
  public function home() {
    return view('home', [
      'recent' => $this->recentShows(32, false)
    ]);
  }

  /**
   * Show a page listing all anime in our database.
   *
   * @return \Illuminate\Http\Response
   */
  public function list(Request $request) {
    $showsPerPage = 52;
    if (!isset($request->page)) {
      return redirect(url('/anime').'?page=1');
    }
    if ((int) $request->page != $request->page) {
      return redirect(url('/anime').'?page='. (int) $request->page);
    }

    if ($request->page < 1) {
      abort(404);
    }
    $shows = Show::orderBy('title', 'asc')->skip(($request->page - 1) * $showsPerPage)->take($showsPerPage)->get();
    if (count($shows) == 0) {
      abort(404);
    }

    return view('anime.list', [
      'left' => $request->page > 1,
      'right' => $request->page < (Show::count() / $showsPerPage),
      'shows' => $shows,
    ]);
  }

  /**
   * Reroute to the last used form of the 'recently uploaded' page.
   *
   * @return \Illuminate\Http\Response
   */
  public function recent() {
    // TODO: make more persistent
    if (session()->has('recentpage_form')) {
      return redirect('anime/recent/'.session()->get('recentpage_form'));
    } else {
      return redirect('anime/recent/grid');
    }
  }

  /**
   * Show the 'recently uploaded' page as a list.
   *
   * @return \Illuminate\Http\Response
   */
  public function recentList() {
    session()->put('recentpage_form', 'list');
    return view('anime.recent_list', [
      'recent' => $this->recentShows(128, false)
    ]);
  }

  /**
   * Show the 'recently uploaded' page as a grid.
   *
   * @return \Illuminate\Http\Response
   */
  public function recentGrid() {
    session()->put('recentpage_form', 'grid');
    return view('anime.recent_grid', [
      'recent' => $this->recentShows(192, true)
    ]);
  }

  /**
   * Show the search page.
   *
   * @return \Illuminate\Http\Response
   */
  public function search(Request $request) {
    $results = [];

    if (isset($request->q)) {
      $this->validate($request, [
        'q' => ['required', 'min:3']
      ], [], [
        'q' => 'query'
      ]);
      $results = MyAnimeList::search($request->q);

      // Expand results which are in our databse
      foreach ($results as $index => $result) {
        $show = Show::where('mal_id', $result->mal_id)->first();
        if (!empty($show)) {
          $results[$index] = $show;
        }
      }
    }

    return view('anime.search', [
      'results' => $results
    ]);
  }
}
