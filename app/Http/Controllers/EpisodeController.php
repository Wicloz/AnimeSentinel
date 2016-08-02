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

    if (count($videos) == 0) {
      abort(404);
    }

    $resolutions = [];
    foreach ($videos as $video) {
      if (!in_array($video->resolution, $resolutions)) {
        $resolutions[] = $video->resolution;
      }
    }
    usort($resolutions, function ($a, $b) {
      $aex = explode('x', $a);
      $a = $aex[0] * $aex[1];
      $bex = explode('x', $b);
      $b = $bex[0] * $bex[1];
      if ($a === $b) return 0;
      return ($a > $b) ? -1 : 1;
    });

    return view('anime.episode', [
      'show' => $show,
      'episode' => $videos[0],
      'videos' => $videos,
      'resolutions' => $resolutions,
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
    if (!visitPage('video_'.$video->episode_id)) {
      $video->hits++;
      $video->save();
    }
    return view('anime.stream', [
      'video' => $video
    ]);
  }
}
