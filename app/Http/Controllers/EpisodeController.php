<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;

use App\Show;
use App\Video;

class EpisodeController extends Controller
{
  /**
   * Redirect the user to the episode page with the highest quality mirror picked.
   *
   * @return \Illuminate\Http\Response
   */
  public function gotoEpisode($show, $translation_type, $episode_num) {
    return redirect(url('/anime/'.$show.'/'.$translation_type.'/episode-'.$episode_num.'/kissanime/1'));
  }

  /**
   * Display a player with the selected mirror.
   * Also show all other available mirrors for the episode.
   *
   * @return \Illuminate\Http\Response
   */
  public function episode(Show $show, $translation_type, $episode_num, $streamer, $mirror) {
    $mirrors = $show->videos()
                   ->episode($translation_type, $episode_num)
                   ->get();
    if (count($mirrors) == 0) {
      abort(404);
    }

    $video = Video::find([
      'show_id' => $show->id,
      'translation_type' => $translation_type,
      'episode_num' => $episode_num,
      'streamer_id' => $streamer,
      'mirror' => $mirror,
    ]);
    if (empty($video)) {
      abort(404);
    }
    if (!visitPage('video_'.$video->id)) {
      $video->hits++;
      $video->save();
    }

    $resolutions = [];
    foreach ($mirrors as $mirror) {
      if (!in_array($mirror->resolution, $resolutions)) {
        $resolutions[] = $mirror->resolution;
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
      'video' => $video,
      'mirrors' => $mirrors,
      'resolutions' => $resolutions,
    ]);
  }

  /**
   * Show a page linking to the video source.
   * This can be used to provide a static link to the source material.
   *
   * @return \Illuminate\Http\Response
   */
  public function static(Video $video) {
    if (!visitPage('video_'.$video->id)) {
      $video->hits++;
      $video->save();
    }
    // TODO: improve this
    return redirect($video->link_video);
  }
}
