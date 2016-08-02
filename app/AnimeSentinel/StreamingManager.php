<?php

namespace App\AnimeSentinel;

use App\Streamer;
use App\Show;

class StreamingManager
{
  /**
   * Finds all video's for a specific show on all streaming sites.
   * Removes all existing episodes and videos for that show.
   * This is used when a new show is added or some shows videos are broken and need to be refreshed.
   */
  public static function findVideosForShow($show) {
    // Mark show as not initialised
    $show->videos_initialised = false;
    $show->save();
    // Grab all streamers data
    $streamers = Streamer::all();

    // For all streamers, request videos for this show
    $videos = [];
    foreach ($streamers as $streamer) {
      if ($streamer->id !== 'youtube' || (!empty($show->show_flags) && $show->show_flags->check_youtube)) {
        $class = '\\App\\AnimeSentinel\\Connectors\\'.$streamer->id;
        $videos = array_merge($videos, $class::seek($show));
      }
    }

    // Remove all existing videos for this show
    $show->videos()->delete();
    // Save the new videos
    VideoManager::saveVideos($videos);
    // Mark show as initialised
    $show->videos_initialised = true;
    $show->save();
  }

  /**
   * Finds all video's for all existing shows on a specific streaming site.
   * This is used when a new streaming site is added.
   */
  public static function findVideosForStreamer($streamer) {
    // Process all shows data in chuncks of 100
    Show::orderBy('id')->chunk(100, function($shows) use ($streamer) {
      foreach ($shows as $show) {
        // Mark show as not initialised
        $show->videos_initialised = false;
        $show->save();
        // Find videos for the show
        $class = '\\App\\AnimeSentinel\\Connectors\\'.$streamer->id;
        $videos = $class::seek($show);
        // Save the videos
        VideoManager::saveVideos($videos);
        // Mark show as initialised
        $show->videos_initialised = true;
        $show->save();
      }
    });
  }

  /**
   * Finds all video's an all streaming sites's recently aired page.
   * This is used for the periodic checks.
   */
  public static function findRecentVideos() {
    // Grab all streamers data
    $streamers = Streamer::all();

    // For all streamers, request an array of videos
    foreach ($streamers as $streamer) {
      if ($streamer->id !== 'youtube') {
        $class = '\\App\\AnimeSentinel\\Connectors\\'.$streamer->id;
        $videos = $class::guard();

        // Process the videos
        foreach ($videos as $video) {
          // Make sure the anime this video belongs to is present in the database
          $show = Show::withTitle($video->show_id)->first();
          if ($show === null) {
            // Add the show to the database
            $show = ShowManager::addShowWithTitle($video->show_id);
            // And remove all videos for this show from the array
            $video->show_id = $show->id; // TODO
          }
          else {
            // Set the show_id on the video
            $video->show_id = $show->id;
          }
        }

        // Save the videos
        VideoManager::saveVideos($videos);
      }
    }
  }
}
