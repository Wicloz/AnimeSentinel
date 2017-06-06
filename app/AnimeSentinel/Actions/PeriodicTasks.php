<?php

namespace App\AnimeSentinel\Actions;

use App\Streamer;
use App\Show;

class PeriodicTasks
{
  /**
   * Finds all video's an all streaming sites's recently aired page.
   * This is used for the periodic checks.
   */
  public static function findRecentEpisodes()
  {
    // Grab all streamers data
    $streamers = Streamer::where('enabled', true)->where('id', '!=', 'youtube')->get();
    $addedShows = [];

    // For all streamers, request required data
    foreach ($streamers as $streamer) {
      $class = '\\App\\AnimeSentinel\\Connectors\\' . $streamer->id;
      try {
        $data = $class::findRecentlyAired();
      } catch (\Exception $e) {
        $data = [];
        mailException('Failed to find recently aired anime', $e, [
          'Streamer Id' => $streamer->id,
        ]);
      }

      foreach ($data as $item) {
        // Get the show related to this data
        $show = Show::withTitle($item['title'])->first();

        // Add the show if it does not exist
        if ($show === null) {
          $show = ShowManager::addShowWithTitle($item['title'], true, 'periodic_high');
          if ($show !== null) {
            $addedShows[] = $show->id;
          }
        }

        else {
          // Try to update the show cache if it does not have a mal id set
          if ($show->mal_id === null) {
            $show = ShowManager::updateShowCache($show->id, false, 'periodic_high');
            if ($show->mal_id !== null) {
              $addedShows[] = $show->id;
            }
          }

          // Otherwise, if this show is not new, and the epsiode does not exist, queue the finding of all videos for the data
          if (!in_array($show->id, $addedShows)) {
            if ($item['translation_type'] === 'all') {
              if (
                $show->videos()->episode('sub', $item['episode_num'])->where('streamer_id', $streamer->id)->first() === null ||
                $show->videos()->episode('dub', $item['episode_num'])->where('streamer_id', $streamer->id)->first() === null
              ) {
                queueJob(new \App\Jobs\AnimeReprocessEpisodes($show, ['sub', 'dub'], $item['episode_num'], $streamer->id), 'periodic_high');
              }
            }
            elseif ($show->videos()->episode($item['translation_type'], $item['episode_num'])->where('streamer_id', $streamer->id)->first() === null) {
              queueJob(new \App\Jobs\AnimeReprocessEpisodes($show, [$item['translation_type']], $item['episode_num'], $streamer->id), 'periodic_high');
            }
          }
        }
      }
    }
  }
}
