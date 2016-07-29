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

  /**
   * Show the page where a video can be watched.
   * This one first finds the video based on the given keys.
   *
   * @return \Illuminate\Http\Response
   */
  public function stream2($show, $translation_type, $episode_num, $streamer, $mirror) {
    return Self::stream(Video::find([
      'show_id' => $show,
      'translation_type' => $translation_type,
      'episode_num' => $episode_num,
      'streamer_id' => $streamer,
      'mirror' => $mirror,
    ]));
  }

  /**
   * Show the page where a video can be watched.
   *
   * @return \Illuminate\Http\Response
   */
  public function stream(Video $video) {
    return view('anime.stream', [
      'video' => $video
    ]);
  }
}
