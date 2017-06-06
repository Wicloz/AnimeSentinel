<?php

namespace App\AnimeSentinel\Actions;

use App\Streamer;
use App\Video;
use Carbon\Carbon;

class VideoManager
{
  /**
   * Saves an array of video objects and queues a job to set their metadata.
   */
  public static function saveVideos($videos, $queue = 'low')
  {
    if (!is_array($videos)) {
      $videos = [$videos];
    }
    foreach ($videos as $video) {
      $video->save();
      if ($video->player_support) {
        queueJob(new \App\Jobs\VideoRefreshLink($video), $queue);
      }
      else {
        $video->setVideoMetaData();
        $video->save();
      }
    }
  }

  /**
   * Finds the video link for the requested video from it's streaming site.
   *
   * @return string
   */
  public static function findVideoLink($video)
  {
    $class = '\\App\\AnimeSentinel\\Connectors\\' . $video->streamer_id;

    if ($video->streamer->enabled) {
      try {
        $mirrors = collect($class::findMirrors($video->link_episode));
        $mirror = $mirrors->where('mirror_id', $video->mirror_id)->first();
      } catch (\Exception $e) {
        if (config('queue.default') !== 'sync') {
          queueJob(new \App\Jobs\VideoRefreshLink($video), 'low');
        }
        $mirror = null;
        mailException('Failed to find a video link', $e, [
          'Video Id' => $video->id,
          'Show Title' => $video->show->title,
          'Show Id' => $video->show_id,
          'Translation Type' => $video->translation_type,
          'Episode Number' => $video->episode_num,
          'Streamer Id' => $video->streamer_id,
          'Mirror Number' => $video->mirror,
        ]);
      }
    }

    if (isset($mirror)) {
      return $mirror['link_video'];
    }
    else {
      return $video->link_video;
    }
  }

  /**
   * Refreshes the video link and sets the metadata for the requested video.
   */
  public static function refreshVideoLinkFor($video, $fromJob = false)
  {
    // Handle job related tasks
    $jobShowId = $video->show->mal_id !== null ? $video->show->mal_id : $video->show->title;
    if (!handleJobFunction('VideoRefreshLink', $jobShowId, ['video_id' => $video->id], $fromJob)) return;

    $video->refreshVideoLink();
    $video->save();
  }
}
