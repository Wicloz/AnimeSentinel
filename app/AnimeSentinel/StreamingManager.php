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
  public static function findRecentEpisodes() {
    // Grab all streamers data
    $streamers = Streamer::all();
    $addedShows = [];

    // For all streamers, request required data
    foreach ($streamers as $streamer) {
      if ($streamer->id !== 'youtube') {
        $class = '\\App\\AnimeSentinel\\Connectors\\'.$streamer->id;
        $data = $class::guard();

        foreach (array_reverse($data) as $item) {
          // Get the show related to this data
          $show = Show::withTitle($item['title'])->first();
          // Add the show if it does not exist
          if ($show === null) {
            $show = ShowManager::addShowWithTitle($item['title'], 'periodic_high');
            $addedShows[] = $show->id;
          }

          else {
            // Try to update the show cache if it does not have a mal id set
            if ($show->mal_id === null) {
              $show = ShowManager::updateShowCache($show->id, false, 'periodic_high');
              if ($show && $show->mal_id !== null) {
                $addedShows[] = $show->id;
              }
              $show = Show::withTitle($item['title'])->first();
            }

            // Otherwise, if this show is not new and the epsiode does not exist, queue the finding of all videos for the data
            if (!in_array($show->id, $addedShows)) {
              if (!isset($item['translation_type']) && (
                $show->videos()->episode('sub', $item['episode_num'])->where('streamer_id', $streamer->id)->first() === null ||
                $show->videos()->episode('dub', $item['episode_num'])->where('streamer_id', $streamer->id)->first() === null
              )) {
                queueJob(new \App\Jobs\AnimeReprocessEpisode($show, ['sub', 'dub'], (int) $item['episode_num'], $streamer->id), 'periodic_high');
              }
              elseif ($show->videos()->episode($item['translation_type'], $item['episode_num'])->where('streamer_id', $streamer->id)->first() === null) {
                queueJob(new \App\Jobs\AnimeReprocessEpisode($show, [$item['translation_type']], (int) $item['episode_num'], $streamer->id), 'periodic_high');
              }
            }
          }
        }
      }
    }
  }
}
