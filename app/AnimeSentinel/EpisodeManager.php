<?php

namespace App\AnimeSentinel;

use App\Streamer;
use App\Video;

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
        // Check whether video link isn't empty
        if (!empty($video->link_video)) {
          $video->save();
        }
      }
    }
  }

  /**
   * Finds all episodes and their video's an all streaming sites's recently aired page.
   */
  public static function findRecentEpisodes() {
    // Grab all streamers data
    $streamers = Streamer::all();

    // For all streamers, request an array of video data and save it
    foreach ($streamers as $streamer) {
      $class = '\\App\\AnimeSentinel\\Connectors\\'.$streamer->id;
      $videos = $class::guard();
      $videos = $class::seek(\App\Show::first());

      foreach ($videos as $video) {
        // Check whether video doesn't already exists in the database and the link isn't empty
        if (count(Video::sameVideo($video)->get()) == 0 && !empty($video->link_video)) {
          $video->save();
        }
      }
    }
  }
}
