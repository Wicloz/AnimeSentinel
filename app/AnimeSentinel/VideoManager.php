<?php

namespace App\AnimeSentinel;

use App\Streamer;
use App\Video;
use Carbon\Carbon;

class VideoManager
{
  /**
   * Saves an array of video objects after:
   * 1. Calling the setVideoMetaData function
   */
  public static function saveVideos($videos) {
    foreach ($videos as $video) {
      $video->setVideoMetaData();
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
