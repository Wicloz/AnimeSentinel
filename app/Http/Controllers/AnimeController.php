<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;

use App\Show;
use App\Video;
use App\AnimeSentinel\MyAnimeList;

class AnimeController extends Controller
{
  protected function recentShows($limit) {
    $recent = Video::where('mirror', 1)
                   ->orderBy('uploadtime', 'desc')
                   ->take($limit)->with('show')->get();
    return $recent;
  }

  /**
   * Show the home page.
   *
   * @return \Illuminate\Http\Response
   */
  public function home() {
    return view('home', [
      'recent' => $this->recentShows(32)
    ]);
  }

  /**
   * Show the 'recently uploaded' page.
   *
   * @return \Illuminate\Http\Response
   */
  public function recent() {
    return view('anime.recent', [
      'recent' => $this->recentShows(64)
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
