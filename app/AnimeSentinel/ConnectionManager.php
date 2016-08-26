<?php

namespace App\AnimeSentinel;

use App\Streamer;
use App\Show;

class ConnectionManager
{
  /**
   * Finds all video's for a specific show on all streaming sites.
   * Removes all existing episodes and videos for that show.
   * This is used when a new show is added or some shows videos are broken and need to be refreshed.
   */
  public static function findVideosForShow($show, $fromJob = false) {
    // Set job values
    $jobShowId = $show->mal_id !== null ? $show->mal_id : $show->title;
    $job_dbdata = [
      ['job_task', '=', 'AnimeFindVideos'],
      ['show_id', '=', $jobShowId],
      ['job_data', '=', json_encode(null)],
    ];
    // Remove any inferior queued jobs
    \App\Job::deleteLowerThan('AnimeFindVideos', $jobShowId);
    // If this is queued as a job, remove it from the queue
    \App\Job::where(array_merge($job_dbdata, [['reserved', '=', 0]]))->delete();
    // Hovever, if that job is in progress, wait for it to complete instead of running this function,
    // but only if this function isn't started from the job
    if (!$fromJob && count(\App\Job::where(array_merge($job_dbdata, [['reserved', '=', 1]]))->get()) > 0) {
      while (count(\App\Job::where(array_merge($job_dbdata, [['reserved', '=', 1]]))->get()) > 0) {
        sleep(1);
      }
      return;
    }

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

    // Mail an anomaly report if no videos were found
    if (count($videos) == 0) {
      mailAnomaly($show, 'Could not find any videos when searching for all videos.', [
        'Run From a Job' => $fromJob ? 'Yes' : 'No',
      ]);
    }
  }

  /**
   * Removes and adds all videos for the requested show and episode.
   */
  public static function reprocessEpsiode($show, $translation_types, $episode_num, $streamer_id = null, $fromJob = false) {
    // Set job values
    $jobShowId = $show->mal_id !== null ? $show->mal_id : $show->title;
    $job_dbdata = [
      ['job_task', '=', 'AnimeReprocessEpisode'],
      ['show_id', '=', $jobShowId],
      ['job_data', '=', json_encode([
        'translation_types' => $translation_types,
        'episode_num' => $episode_num,
        'streamer_id' => $streamer_id,
      ])],
    ];
    // Remove any inferior queued jobs
    \App\Job::deleteLowerThan('AnimeReprocessEpisode', $jobShowId);
    // If this is queued as a job, remove it from the queue
    \App\Job::where(array_merge($job_dbdata, [['reserved', '=', 0]]))->delete();
    // Hovever, if that job is in progress, wait for it to complete instead of running this function,
    // but only if this function isn't started from the job
    if (!$fromJob && count(\App\Job::where(array_merge($job_dbdata, [['reserved', '=', 1]]))->get()) > 0) {
      while (count(\App\Job::where(array_merge($job_dbdata, [['reserved', '=', 1]]))->get()) > 0) {
        sleep(1);
      }
      return;
    }

    // Mark show as not initialised
    $show->videos_initialised = false;
    $show->save();
    // Grab all streamers data
    $streamers = Streamer::all();

    // For all applicable streamers, request videos for this episode
    $videosRaw = []; $videos = [];
    foreach ($streamers as $streamer) {
      if (($streamer_id === null || $streamer_id === $streamer->id) && ($streamer->id !== 'youtube' || (!empty($show->show_flags) && $show->show_flags->check_youtube))) {
        $class = '\\App\\AnimeSentinel\\Connectors\\'.$streamer->id;
        $videosRaw = array_merge($videosRaw, $class::seek($show, $episode_num));
      }
    }
    foreach ($videosRaw as $video) {
      if (in_array($video->translation_type, $translation_types)) {
        $videos[] = $video;
      }
    }

    // Remove all existing videos for this episode
    foreach ($translation_types as $translation_type) {
      if ($streamer_id === null) {
        $show->videos()->episode($translation_type, $episode_num)->delete();
      } else {
        $show->videos()->episode($translation_type, $episode_num)->where('streamer_id', $streamer_id)->delete();
      }
    }
    // Save the new videos
    VideoManager::saveVideos($videos);
    // Mark show as initialised
    $show->videos_initialised = true;
    $show->save();

    // Mail an anomaly report if no videos were found
    if (count($videos) == 0) {
      mailAnomaly($show, 'Could not find any videos when reprocessing an episode.', [
        'Translation Types' => json_encode($translation_types),
        'Epsiode Number' => $episode_num,
        'Streaming Site' => isset($streamer_id) ? $streamer_id : 'NA',
        'Run From a Job' => $fromJob ? 'Yes' : 'No',
      ]);
    }
  }

  /**
   * Finds all video's for all existing shows on a specific streaming site.
   * This is used when a new streaming site is added.
   */
  public static function findVideosForStreamer($streamer) {
    // Set job values
    $job_dbdata = [
      ['job_task', '=', 'StreamerFindVideos'],
      ['show_id', '=', null],
      ['job_data', '=', json_encode(['streamer_id' => $streamer->id])],
    ];
    // If this is queued as a job, remove it from the queue
    \App\Job::where(array_merge($job_dbdata, [['reserved', '=', 0]]))->delete();
    // Hovever, if that job is in progress, wait for it to complete instead of running this function,
    // but only if this function isn't started from the job
    if (!$fromJob && count(\App\Job::where(array_merge($job_dbdata, [['reserved', '=', 1]]))->get()) > 0) {
      while (count(\App\Job::where(array_merge($job_dbdata, [['reserved', '=', 1]]))->get()) > 0) {
        sleep(1);
      }
      return;
    }

    // Process all shows data in chuncks of 100
    Show::orderBy('id')->chunk(100, function ($shows) use ($streamer) {
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
            if ($streamer->id === 'kisscartoon') {
              $show = ShowManager::addShowWithTitle($item['title'], 'periodic_high', false, false);
            } else {
              $show = ShowManager::addShowWithTitle($item['title'], 'periodic_high');
            }
            if ($show !== null) {
              $addedShows[] = $show->id;
            }
          }

          else {
            // Try to update the show cache if it does not have a mal id set
            if ($show->mal_id === null) {
              $show = ShowManager::updateShowCache($show->id, false, 'periodic_high');
              if (!empty($show) && $show->mal_id !== null) {
                $addedShows[] = $show->id;
              } else {
                $show = Show::withTitle($item['title'])->first();
              }
            }

            // Otherwise, if this show is not new and the epsiode does not exist, queue the finding of all videos for the data
            if (!in_array($show->id, $addedShows)) {
              if (!isset($item['translation_type'])) {
                if ($show->videos()->episode('sub', $item['episode_num'])->where('streamer_id', $streamer->id)->first() === null ||
                    $show->videos()->episode('sub', $item['episode_num'])->where('streamer_id', $streamer->id)->first() === null)
                {
                  queueJob(new \App\Jobs\AnimeReprocessEpisode($show, ['sub', 'dub'], (int) $item['episode_num'], $streamer->id), 'periodic_high');
                }
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
