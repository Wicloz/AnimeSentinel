<?php

namespace App\AnimeSentinel;

use App\Streamer;
use App\Video;
use Carbon\Carbon;

class VideoManager
{
  /**
   * Saves an array of video objects and queues a job to set their metadata.
   */
  public static function saveVideos($videos, $queue = 'low') {
    foreach ($videos as $video) {
      $video->save();
      if (playerSupport($video->link_video)) {
        queueJob(new \App\Jobs\VideoSetMetadata($video), $queue);
      } else {
        Self::setMetaDataFor($video);
      }
    }
  }

  /**
   * Finds the video link for the requested video from it's streaming site.
   *
   * @return string
   */
  public static function findVideoLink($video) {
    $class = '\\App\\AnimeSentinel\\Connectors\\'.$video->streamer_id;
    return $class::findVideoLink($video);
  }

  /**
   * Sets the metadata for the requested video.
   */
  public static function setMetaDataFor($video, $fromJob = false) {
    // Set job values
    $jobShowId = $video->show->mal_id !== null ? $video->show->mal_id : $video->show->title;
    $job_dbdata = [
      ['job_task', '=', 'VideoSetMetadata'],
      ['show_id', '=', $jobShowId],
      ['job_data', '=', json_encode($video->id)],
    ];
    // Remove any inferior queued jobs
    \App\Job::deleteLowerThan('VideoSetMetadata', $jobShowId);
    // If this is queued as a job, remove it from the queue
    \App\Job::where(array_merge($job_dbdata, [['reserved_at', '=', null]]))->delete();
    // Hovever, if that job is in progress, wait for it to complete instead of running this function,
    // but only if this function isn't started from the job
    if (!$fromJob && count(\App\Job::where(array_merge($job_dbdata, [['reserved_at', '!=', null]]))->get()) > 0) {
      while (count(\App\Job::where(array_merge($job_dbdata, [['reserved_at', '!=', null]]))->get()) > 0) {
        sleep(1);
      }
      return;
    }

    $video->setVideoMetaData();
    $video->save();
  }
}
