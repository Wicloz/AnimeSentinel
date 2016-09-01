<?php

namespace App\AnimeSentinel;

use App\Streamer;
use App\Video;
use Carbon\Carbon;

class VideoManager
{
  /**
   * Saves an array of video objects and queues a job to set their metadata.
   */
  public static function saveVideos($videos, $queue = 'low') {
    foreach ($videos as $video) {
      $video->save();
      if (playerSupport($video->link_video)) {
        queueJob(new \App\Jobs\VideoRefreshLink($video), $queue);
      } else {
        $video->setVideoMetaData();
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
    return $class::findVideoLink($video);
  }

  /**
   * Refreshes the video link and sets the metadata for the requested video.
   */
  public static function refreshVideoLinkFor($video, $job) {
    $video->refreshVideoLink();
    $video->save();
  }
}
