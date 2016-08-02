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
      $recent = Video::orderBy('uploadtime', 'desc')->orderBy('show_id', 'desc')->orderBy('episode_num', 'desc')
                     ->groupBy(['show_id', 'translation_type', 'episode_num'])->distinct()
                     ->take($limit)->with('show')->get();
    }

    else {
      $recent = Video::where('mirror', 1)
                     ->orderBy('uploadtime', 'desc')
                     ->orderBy('show_id', 'desc')
                     ->orderBy('episode_num', 'desc')
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
      'recent' => $this->recentShows(64, false)
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
      'recent' => $this->recentShows(128, true)
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
      $results = MyAnimeList::searchApi($request->q);

      // Sort results by score
      usort($results, function ($a, $b) {
        if ($a->score === $b->score) return 0;
        return ($a->score > $b->score) ? -1 : 1;
      });

      // Expand results which are in our databse
      foreach ($results as $index => $result) {
        $show = Show::where('mal_id', $result->id)->first();
        if (!empty($show)) {
          $results[$index] = $show;
        } else {
          $results[$index]->details_url = 'http://myanimelist.net/anime/'.$result->id;
          $results[$index]->thumbnail_url = $result->image;
        }
      }
    }

    return view('anime.search', [
      'results' => $results
    ]);
  }
}
