<?php

namespace App\AnimeSentinel\Actions;

use App\Streamer;
use App\Show;

class FindVideos
{
  /**
   * Finds all video's for a specific show on all streaming sites.
   * Removes all existing episodes and videos for that show.
   * This is used when a new show is added or some shows videos are broken and need to be refreshed.
   */
  public static function findVideosForShow($show, $fromJob = false) {
    // Handle job related tasks
    $jobShowId = $show->mal_id !== null ? $show->mal_id : $show->title;
    if (!handleJobFunction('AnimeFindVideos', $jobShowId, null, $fromJob)) return;

    // Mark show as not initialised
    $show->videos_initialised = false;
    $show->save();
    // Remove all existing videos for this show
    $show->videos()->delete();

    // Grab all streamers data
    $streamers = Streamer::all();

    // Find and save videos for each streamer
    $videosFound = 0;
    foreach ($streamers as $streamer) {
      $class = '\\App\\AnimeSentinel\\Connectors\\'.$streamer->id;
      $videosFound += $class::findVideosForShow($show);
    }

    // Mark show as initialised
    $show->videos_initialised = true;
    $show->save();

    // Mail an anomaly report if no videos were found
    if ($videosFound <= 0) {
      mailAnomaly($show, 'Could not find any videos when searching for all videos.', [
        'Ran From a Job' => $fromJob ? 'Yes' : 'No',
      ]);
    }
  }

  /**
   * Removes and adds all videos for the requested show and episode.
   */
  public static function reprocessEpsiode($show, $translation_types, $episode_num, $streamer_id = null, $fromJob = false) {
    // Handle job related tasks
    $jobShowId = $show->mal_id !== null ? $show->mal_id : $show->title;
    if (!handleJobFunction('AnimeReprocessEpisode', $jobShowId, [
      'translation_types' => $translation_types,
      'episode_num' => $episode_num,
      'streamer_id' => $streamer_id,
    ], $fromJob)) return;

    // Mark show as not initialised
    $show->videos_initialised = false;
    $show->save();
    // Remove all existing videos for this episode
    foreach ($translation_types as $translation_type) {
      if ($streamer_id === null) {
        $show->videos()->episode($translation_type, $episode_num)->delete();
      } else {
        $show->videos()->episode($translation_type, $episode_num)->where('streamer_id', $streamer_id)->delete();
      }
    }

    // Grab all streamers data
    if ($streamer_id === null) {
      $streamers = Streamer::all();
    } else {
      $streamers = Streamer::where('id', $streamer_id)->get();
    }

    // Find and save videos for each streamer
    $videosFound = 0;
    foreach ($streamers as $streamer) {
      $class = '\\App\\AnimeSentinel\\Connectors\\'.$streamer->id;
      $videosFound += $class::findVideosForShow($show, $translation_types, $episode_num);
    }

    // Mark show as initialised
    $show->videos_initialised = true;
    $show->save();

    // Mail an anomaly report if no videos were found
    if ($videosFound <= 0) {
      mailAnomaly($show, 'Could not find any videos when reprocessing an episode.', [
        'Translation Types' => json_encode($translation_types),
        'Epsiode Number' => $episode_num,
        'Streaming Site' => isset($streamer_id) ? $streamer_id : 'NA',
        'Ran From a Job' => $fromJob ? 'Yes' : 'No',
      ]);
    }
  }

  /**
   * Finds all video's for all existing shows on a specific streaming site.
   * This is used when a new streaming site is added.
   */
  public static function findVideosForStreamer($streamer, $fromJob = false) {
    // Handle job related tasks
    if (!handleJobFunction('StreamerFindVideos', null, ['streamer_id' => $streamer->id], $fromJob)) return;

    $class = '\\App\\AnimeSentinel\\Connectors\\'.$streamer->id;

    // Process all shows in chunks of 8
    Show::orderBy('id')->chunk(8, function ($shows) use ($streamer) {
      foreach ($shows as $show) {
        // Mark show as not initialised
        $show->videos_initialised = false;
        $show->save();
        // Find and save videos for the show
        $class::findVideosForShow($show);
        // Mark show as initialised
        $show->videos_initialised = true;
        $show->save();
      }
    });
  }
}
