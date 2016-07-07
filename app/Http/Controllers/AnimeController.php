<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Show;
use App\Video;

class AnimeController extends Controller
{
  protected function recentShows() {
    $recent = Video::orderBy('uploadtime', 'desc')
              ->take(50)
              ->get();
    $shows = [];

    foreach ($recent as $index => $video) {
      $shows[$index] = $video->show;
      $shows[$index]->this_episode = $video->episode_num;
      $shows[$index]->this_translation = $video->translation_type;
    }

    return $shows;
  }

  protected function malSearchResults ($query) {
    // TODO
    return [];
  }

  /**
   * Show the home page.
   *
   * @return \Illuminate\Http\Response
   */
  public function home() {
    return view('home', [
      'shows' => $this->recentShows()
    ]);
  }

  /**
   * Show the 'recently uploaded' page.
   *
   * @return \Illuminate\Http\Response
   */
  public function recent() {
    return view('anime.recent', [
      'shows' => $this->recentShows()
    ]);
  }

  /**
   * Show an empty search results page.
   *
   * @return \Illuminate\Http\Response
   */
  public function searchEmpty() {
    return view('anime.search', [
      'results' => []
    ]);
  }

  /**
   * Show the search page with results.
   *
   * @return \Illuminate\Http\Response
   */
  public function searchPost(Request $request) {
    $this->validate($request, [
      'query' => ['required', 'min:3']
    ]);
    $results = $this->malSearchResults($request->query);

    // TODO: expand known anime

    return view('anime.search', [
      'results' => $results
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
  public function episode(Show $show, $translation_type, $episode_num) {
    $videos = $show->videos()
                   ->episode($translation_type, $episode_num)
                   ->get();

    return view('anime.episode', [
      'show' => $show,
      'translation' => $translation_type,
      'number' => $episode_num,
      'videos' => $videos,
    ]);
  }
}
