<?php

namespace App\AnimeSentinel;

use App\Streamer;
use App\Video;
use Carbon\Carbon;

class VideoManager
{
  /**
   * Saves the passed in video objects after:
   * 1. Setting the cache_updated_at property to the current date and time
   */
  public static function saveVideos($videos) {
    foreach ($videos as $video) {
      $video->cache_updated_at = Carbon::now();
      $video->save();
    }
  }

  /**
   * Finds the video link for the requested video from it's streaming site.
   *
   * @return string
   */
  public static function findVideoLink($video) {
    $class = '\\App\\AnimeSentinel\\Connectors\\'.$video->streamer_id;
    return $class::findVideoLink($video);
  }
}
