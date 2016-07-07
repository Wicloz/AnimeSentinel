<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;

use App\Show;
use App\Video;

class EpisodeController extends Controller
{
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
