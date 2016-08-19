<?php

namespace App\AnimeSentinel;

use App\Show;
use Carbon\Carbon;

class ShowManager
{
  /**
   * Adds the show with the requested title to the database.
   * Will also add any currently existing episodes.
   */
  public static function addShowWithTitle($title, $queue = 'default', $fromJob = false, $allowNonMal = true) {
    // Set job values
    $job_dbdata = [
      ['job_task', '=', 'ShowAdd'],
      ['show_id', '=', $title],
      ['job_data', '=', json_encode(null)],
    ];
    // Remove any inferior queued jobs
    \App\Job::deleteLowerThan('ShowAdd', $title);
    // If this is queued as a job, remove it from the queue
    \App\Job::where(array_merge($job_dbdata, [['reserved', '=', 0]]))->delete();
    // Hovever, if that job is in progress, wait for it to complete instead of running this function,
    // but only if this function isn't started from the job
    if (!$fromJob && count(\App\Job::where(array_merge($job_dbdata, [['reserved', '=', 1]]))->get()) > 0) {
      while (count(\App\Job::where(array_merge($job_dbdata, [['reserved', '=', 1]]))->get()) > 0) {
        sleep(1);
      }
      return Show::withTitle($title)->first();
    }

    // Confirm this show isn't already in our databse
    $dbshow = Show::withTitle($title)->first();
    if (!empty($dbshow)) {
      flash_error('The requested show has already been added to the database.');
      if (!$dbshow->videos_initialised) {
        queueJob(new \App\Jobs\AnimeFindVideos($dbshow), $queue);
      }
      return $dbshow;
    }

    // Try to find this show on MAL and get it's id
    $mal_id = MyAnimeList::getMalIdForTitle($title);

    if (isset($mal_id)) {
      // Create and return a new show with the proper data
      return Self::addShowWithMalId($mal_id, $queue, $fromJob);
    } elseif($allowNonMal) {
      // Create a mostly empty show because we don't have MAL data
      $show = Show::create([
        'title' => $title,
        'alts' => [$title],
        'description' => 'No Description Available',
      ]);
      // Mail an anomaly report
      mailAnomaly($show, 'Could not find show on MAL.', [
        'Run From a Job' => $fromJob ? 'Yes' : 'No',
      ]);
      // Finalize and return the show
      return Self::finalizeShowAdding($show, $queue);
    } else {
      return null;
    }
  }

  /**
   * Adds the show with the requested MAL id to the database.
   * Will also add any currently existing episodes.
   */
  public static function addShowWithMalId($mal_id, $queue = 'default', $fromJob = false) {
    // Set job values
    $job_dbdata = [
      ['job_task', '=', 'ShowAdd'],
      ['show_id', '=', $mal_id],
      ['job_data', '=', json_encode(null)],
    ];
    // Remove any inferior queued jobs
    \App\Job::deleteLowerThan('ShowAdd', $mal_id);
    // If this is queued as a job, remove it from the queue
    \App\Job::where(array_merge($job_dbdata, [['reserved', '=', 0]]))->delete();
    // Hovever, if that job is in progress, wait for it to complete instead of running this function,
    // but only if this function isn't started from the job
    if (!$fromJob && count(\App\Job::where(array_merge($job_dbdata, [['reserved', '=', 1]]))->get()) > 0) {
      while (count(\App\Job::where(array_merge($job_dbdata, [['reserved', '=', 1]]))->get()) > 0) {
        sleep(1);
      }
      return Show::where('mal_id', $mal_id)->first();
    }

    // Confirm this show isn't already in our databse
    $dbshow = Show::where('mal_id', $mal_id)->first();
    if (!empty($dbshow)) {
      flash_error('The requested show has already been added to the database.');
      if (!$dbshow->videos_initialised) {
        queueJob(new \App\Jobs\AnimeFindVideos($dbshow), $queue);
      }
      return $dbshow;
    }

    // Create a new show with the proper data
    $show = Show::create(MyAnimeList::getAnimeData($mal_id));
    Self::updateThumbnail($show);

    // Finalize and return the show
    return Self::finalizeShowAdding($show, $queue);
  }

  private static function finalizeShowAdding($show, $queue) {
    // Set the cache updated time
    $show->cache_updated_at = Carbon::now();
    $show->save();

    // Queue the finding of videos
    queueJob(new \App\Jobs\AnimeFindVideos($show), $queue);

    // Return the show object
    return $show;
  }

  /**
   * Updates the cached database information for the requested show.
   * If episodes is set to true, also updates episode information.
   */
  public static function updateShowCache($show_id, $episodes = false, $queue = 'default', $fromJob = false) {
    $show = Show::find($show_id);
    // Set job values
    $jobShowId = $show->mal_id !== null ? $show->mal_id : $show->title;
    $job_dbdata = [
      ['job_task', '=', 'ShowUpdate'],
      ['show_id', '=', $jobShowId],
      ['job_data', '=', json_encode(null)],
    ];
    // Remove any inferior queued jobs
    \App\Job::deleteLowerThan('ShowUpdate', $jobShowId);
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

    // If the mal id is not known yet, try to find it first
    if (!isset($show->mal_id)) {
      $mal_id = MyAnimeList::getMalIdForTitle($show->title);
      if (isset($mal_id)) {
        $otherShow = Show::where('mal_id', $mal_id)->first();
        if (empty($otherShow)) {
          $show->update(MyAnimeList::getAnimeData($mal_id));
          Self::updateThumbnail($show);
          $episodes = true;
        } else {
          $show->delete();
          return false;
        }
      }
    }

    // Otherwise just update the cache
    else {
      $show->update(MyAnimeList::getAnimeData($show->mal_id));
      Self::updateThumbnail($show);
    }

    // Set the cache updated time
    $show->cache_updated_at = Carbon::now();
    $show->save();

    // Queue the finding of videos if requested
    if ($episodes) {
      queueJob(new \App\Jobs\AnimeFindVideos($show), $queue);
    }

    // Return the show object
    return $show;
  }

  /**
   * Downloads the show's thumbnail from MAL.
   */
  private static function updateThumbnail($show) {
    if (!empty($show->thumbnail_id)) {
      $remote = 'http://cdn.myanimelist.net/images/anime/'.str_replace('-', '/', $show->thumbnail_id);
      $local = __DIR__.'/../../public/media/thumbnails/'.$show->thumbnail_id;
      copy($remote, $local);
    }
  }
}
