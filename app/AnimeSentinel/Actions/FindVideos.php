<?php

namespace App\AnimeSentinel\Actions;

use App\Streamer;
use App\Show;
use App\Video;
use Carbon\Carbon;

class FindVideos
{
  /**
   * Finds all video's for a specific show on all streaming sites.
   * Removes all existing episodes and videos for that show.
   * This is used when a new show is added or some shows videos are broken and need to be refreshed.
   */
  public static function findVideosForShow($show, $fromJob = false)
  {
    // Handle job related tasks
    $jobShowId = $show->mal_id !== null ? $show->mal_id : $show->title;
    if (!handleJobFunction('AnimeFindVideos', $jobShowId, null, $fromJob)) return;

    Self::reprocessEpsiodes($show, ['sub', 'dub'], null, null, $fromJob);
  }

  /**
   * Removes and adds all videos for the requested show, episode and streamer.
   */
  public static function reprocessEpsiodes($show, $translation_types = ['sub', 'dub'], $episode_num = null, $streamer_id = null, $fromJob = false)
  {
    // Handle job related tasks
    $jobShowId = $show->mal_id !== null ? $show->mal_id : $show->title;
    if (!handleJobFunction('AnimeReprocessEpisodes', $jobShowId, [
      'translation_types' => $translation_types,
      'episode_num' => $episode_num,
      'streamer_id' => $streamer_id,
    ], $fromJob)
    ) return;

    // Mark show as not initialised
    $show->videos_initialised = false;
    $show->save();
    // Remove all existing videos for this episode
    $query = Video::where('show_id', $show->id)->whereIn('translation_type', $translation_types);
    if ($episode_num !== null) {
      $query->where('episode_num', $episode_num);
    }
    if ($streamer_id !== null) {
      $query->where('streamer_id', $streamer_id);
    }
    $query->delete();

    // Grab all streamers data
    if ($streamer_id === null) {
      $streamers = Streamer::where('enabled', true)->get();
    }
    else {
      $streamers = Streamer::where('enabled', true)->where('id', $streamer_id)->get();
    }
    if (empty($streamers)) {
      $streamers = [];
    }

    // Find and save videos for each streamer
    $videosFound = 0;
    foreach ($streamers as $streamer) {
      $class = '\\App\\AnimeSentinel\\Connectors\\' . $streamer->id;
      try {
        $videosFound += $class::findVideosForShow($show, $translation_types, $episode_num);
      } catch (\Exception $e) {
        if (config('queue.default') !== 'sync') {
          queueJob(new \App\Jobs\AnimeReprocessEpisodes($show, $translation_types, $episode_num, $streamer->id));
        }
        mailException('Failed to find videos for show episode', $e, [
          'Show Title' => $show->title,
          'Show Id' => $show->id,
          'Streaming Site' => $streamer->id,
          'Translation Types' => json_encode($translation_types),
          'Epsiode Number' => isset($episode_num) ? $episode_num : 'NA',
          'Ran From a Job' => $fromJob ? 'Yes' : 'No',
        ]);
      }
    }

    // Reprocess episodes once a day for 4/5 days after they're uploaded to catch delayed uploads of HD videos and other changes
    if (config('queue.default') !== 'sync') {
      $delays = [1, 2, 4, 8, 24];

      $query = Video::where('show_id', $show->id)->whereIn('translation_type', $translation_types);
      if ($episode_num !== null) {
        $query->where('episode_num', $episode_num);
      }
      if ($streamer_id !== null) {
        $query->where('streamer_id', $streamer_id);
      }
      $videos = $query->distinctOn(['show_id', 'translation_type', 'episode_num', 'streamer_id'], ['uploadtime' => 'desc', 'id' => 'desc'])->get();

      foreach ($videos as $video) {
        foreach ($delays as $delay) {
          if ($video->uploadtime->diffInHours(Carbon::now(), false) < $delay) {
            queueJob((new \App\Jobs\AnimeReprocessEpisodes($show, [$video->translation_type], $video->episode_num, $video->streamer_id))->delay($video->uploadtime->addHours($delay)->addMinutes(2)));
            break;
          }
        }
      }
    }

    // Mark show as initialised
    $show->videos_initialised = true;
    $show->save();

    // Mail an anomaly report if no videos were found
    if ($videosFound <= 0) {
      mailAnomaly($show, 'Could not find any videos when reprocessing an episode.', [
        'Translation Types' => json_encode($translation_types),
        'Epsiode Number' => isset($episode_num) ? $episode_num : 'NA',
        'Streaming Site' => isset($streamer_id) ? $streamer_id : 'NA',
        'Ran From a Job' => $fromJob ? 'Yes' : 'No',
      ]);
    }
  }

  /**
   * Finds all video's for all existing shows on a specific streaming site.
   * This is used when a new streaming site is added.
   */
  public static function findVideosForStreamer($streamer, $fromJob = false)
  {
    // Handle job related tasks
    if (!handleJobFunction('StreamerFindVideos', null, ['streamer_id' => $streamer->id], $fromJob)) return;

    $class = '\\App\\AnimeSentinel\\Connectors\\' . $streamer->id;

    // Process all shows in chunks of 8
    Show::orderBy('id')->chunk(8, function ($shows) use ($streamer, $class) {
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
