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

    // For all streamers, request required data
    foreach ($streamers as $streamer) {
      if ($streamer->id !== 'youtube') {
        $class = '\\App\\AnimeSentinel\\Connectors\\'.$streamer->id;
        $data = $class::guard();

        // Process the data to find all videos
        foreach ($data as $item) {
          // Get the show related to this data
          $show = Show::withTitle($item->title)->first();
          // Add the show if it does not exist
          if ($show === null) {
            ShowManager::addShowWithTitle($item->title);
          }

          // Otherwise, find all videos for the data
          else {
            $translation_type = !isset($item->translation_type) ? ['sub', 'dub'] : [$item->translation_type];
            VideoManager::reprocessEpsiode($show, $translation_type, $item->episode_num, $streamer->id);
          }
        }
      }
    }
  }
}
