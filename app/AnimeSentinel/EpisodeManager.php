<?php

namespace App\AnimeSentinel;

use App\Streamer;
use App\Show;
use App\Video;

class EpisodeManager
{
  private static function saveVideos($videos) {
    foreach ($videos as $video) {
      // Check whether the video doesn't already exists in the database
      if (count(Video::sameVideo($video)) == 0) {
        $video->save();
      }
    }
  }

  /**
   * Finds all video's for a specific show on all streaming sites.
   * Removes all existing episodes and videos for that show.
   * This is used when a new show is added or some shows videos are broken and need to be refreshed.
   */
  public static function findVideosForShow($show) {
    // Grab all streamers data
    $streamers = Streamer::all();

    // For all streamers, request an array of video objects
    $videos = [];
    foreach ($streamers as $streamer) {
      if ($streamer->id !== 'youtube' || (!empty($show->show_flags) && $show->show_flags->check_youtube)) {
        $class = '\\App\\AnimeSentinel\\Connectors\\'.$streamer->id;
        $videos = array_merge($videos, $class::seek($show));
      }
    }

    // Remove all existing videos
    $show->videos()->delete();
    // Save the new videos
    Self::saveVideos($videos);
  }

  /**
   * Finds all video's an all streaming sites's recently aired page.
   * This is used for the periodic checks.
   */
  public static function findRecentVideos() {
    // Grab all streamers data
    $streamers = Streamer::all();

    // For all streamers, request an array of video objects
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
        Self::saveVideos($videos);
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

      // For all shows, request an array of video objects
      foreach ($shows as $show) {
        $class = '\\App\\AnimeSentinel\\Connectors\\'.$streamer->id;
        $videos = $class::seek($show);
        // Save the videos
        Self::saveVideos($videos);
      }
    });
  }
}
