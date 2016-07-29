<?php

namespace App\AnimeSentinel;

use App\Streamer;
use App\Show;
use App\Video;

class EpisodeManager
{
  /**
   * Finds all video's for a specific show on all streaming sites.
   * Removes all existing episodes and videos for that show beforehand.
   * This is used when a new show is added or some shows videos are broken and need to be refreshed.
   */
  public static function findVideosForShow($show) {
    // Grab all streamers data
    $streamers = Streamer::all();

    // Remove all existing videos
    $show->videos()->delete();

    // For all streamers, request an array of video objects and save them
    foreach ($streamers as $streamer) {
      $class = '\\App\\AnimeSentinel\\Connectors\\'.$streamer->id;
      $videos = $class::seek($show);
      foreach ($videos as $video) {
        $video->save();
      }
    }
  }

  /**
   * Finds all video's an all streaming sites's recently aired page.
   * This is used for the periodic checks.
   */
  public static function findRecentVideos() {
    // Grab all streamers data
    $streamers = Streamer::all();

    // For all streamers, request an array of video objects and save them
    foreach ($streamers as $streamer) {
      $class = '\\App\\AnimeSentinel\\Connectors\\'.$streamer->id;
      $videos = $class::guard();

      foreach ($videos as $video) {
        // Make sure the anime this video belongs to is present in the database
        $show = Show::withTitle($video->showtitle)->first();
        if ($show === null) {
          $show = ShowManager::addShowWithTitle($video->showtitle);
        }
        // Set the show_id on the video
        $video->show_id = $show->id;
        $video->showtitle = null;
        // Check whether the video doesn't already exists in the database
        if (count(Video::sameVideo($video)) == 0) {
          $video->save();
        }
      }
    }
  }

  /**
   * Finds all video's for all existing shows on a specific streaming site.
   * This is used when a new streaming site is added.
   */
  public static function findVideosForStreamer($streamer) {
    // Process all shows data in chuncks of 100
    Show::orderBy('id')->chunk(100, function($shows) use ($streamer) {

      // For all shows, request an array of video objects and save them
      foreach ($shows as $show) {
        $class = '\\App\\AnimeSentinel\\Connectors\\'.$streamer->id;
        $videos = $class::seek($show);

        foreach ($videos as $video) {
          // Check whether the video doesn't already exists in the database
          if (count(Video::sameVideo($video)) == 0) {
            $video->save();
          }
        }
      }
    });
  }
}
