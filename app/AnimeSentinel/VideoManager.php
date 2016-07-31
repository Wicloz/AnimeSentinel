<?php

namespace App\AnimeSentinel;

use App\Video;

class VideoManager
{
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
