<?php

namespace App\AnimeSentinel;

use App\Streamer;
use App\Video;

class VideoManager
{
  /**
   * Saves the passed in video objects after:
   * 1. Making sure they don't already exist in the database.
   */
  public static function saveVideos($videos) {
    foreach ($videos as $video) {
      // Check whether the video doesn't already exists in the database
      if (count(Video::sameVideo($video)) == 0) {
        $video->save();
      }
    }
  }

  /**
   * Finds the video link for the requested video from it's streaming site.
   *
   * @return string
   */
  public static function findVideoLink($video) {
    $class = '\\App\\AnimeSentinel\\Connectors\\'.$video->streamer_id;
    return $class::videoLink($video);
  }
}
