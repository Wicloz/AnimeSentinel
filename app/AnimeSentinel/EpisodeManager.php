<?php

namespace App\AnimeSentinel;

use App\Streamer;

class EpisodeManager
{
  /**
   * Finds all epsiodes and video's for a specific show on all streaming sites.
   * Removes all existing episodes and videos for that show beforehand.
   */
  public static function findEpsiodesFor($show) {
    // Grab all streamers data
    $streamers = Streamer::all();

    // Remove all existing videos
    $show->videos()->delete();

    // For all streamers, request an array of video data and save it
    foreach ($streamers as $streamer) {
      $class = '\\App\\AnimeSentinel\\Connectors\\'.$streamer->id;
      $videos = $class::seek($show);
      foreach ($videos as $video) {
        $video->save();
      }
    }
  }
}
