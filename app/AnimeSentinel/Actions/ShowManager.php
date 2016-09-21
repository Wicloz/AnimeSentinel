<?php

namespace App\AnimeSentinel\Actions;

use App\Show;
use Carbon\Carbon;
use App\AnimeSentinel\MyAnimeList;

class ShowManager
{
  /**
   * Adds the show with the requested title to the database.
   * Will also add any currently existing episodes.
   */
  public static function addShowWithTitle($title, $allowNonMal, $queue = 'default', $fromJob = false) {
    // Handle job related tasks
    if (!handleJobFunction('ShowAdd', $title, null, $fromJob)) return Show::withTitle($title)->first();

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
      // Finalize the show
      $show = Self::finalizeShowAdding($show, $queue);
      // Mail an anomaly report
      mailAnomaly($show, 'Could not find show on MAL.', [
        'Ran From a Job' => $fromJob ? 'Yes' : 'No',
      ]);
      // Return the show
      return $show;
    } else {
      return null;
    }
  }

  /**
   * Adds the show with the requested MAL id to the database.
   * Will also add any currently existing episodes.
   */
  public static function addShowWithMalId($mal_id, $queue = 'default', $fromJob = false) {
    // Handle job related tasks
    if (!handleJobFunction('ShowAdd', $mal_id, null, $fromJob)) return Show::where('mal_id', $mal_id)->first();

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
    // Handle job related tasks
    $jobShowId = $show->mal_id !== null ? $show->mal_id : $show->title;
    if (!handleJobFunction('ShowUpdate', $jobShowId, null, $fromJob)) return Show::find($show_id);

    // If the mal id is not known yet, try to find it first
    if (!isset($show->mal_id)) {
      $mal_id = MyAnimeList::getMalIdForTitle($show->title);
      if (isset($mal_id)) {
        $otherShow = Show::where('mal_id', $mal_id)->first();
        if (!empty($otherShow)) {
          $show->delete();
          $show = $otherShow;
        }
        $show->update(MyAnimeList::getAnimeData($mal_id));
        Self::updateThumbnail($show);
        $episodes = true;
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
      $remote = 'https://myanimelist.cdn-dena.com/images/anime/'.str_replace('-', '/', $show->thumbnail_id);
      $local = public_path('media/thumbnails/'.$show->thumbnail_id);
      copy($remote, $local);
    }
  }
}
