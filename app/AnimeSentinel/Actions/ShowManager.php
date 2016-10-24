<?php

namespace App\AnimeSentinel\Actions;

use App\Show;
use Carbon\Carbon;
use App\AnimeSentinel\MyAnimeList;

class ShowManager
{
  private static function handleExistingShow($show, $queue) {
    if ($show !== null) {
      flash_error('The requested anime has already been added to the database.');
      if (!$show->videos_initialised) {
        queueJob(new \App\Jobs\AnimeFindVideos($show), $queue);
      }
      return true;
    }
    return false;
  }

  private static function updateShowData($show, $data, $episodes = false, $queue = 'default') {
    // Update the show
    if ($show === null) {
      $show = Show::create($data);
    } else {
      $show->update($data);
    }
    $show = $show->fresh();

    // Update the thumbnails
    Self::updateThumbnails($show);

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
   * Adds the show with the requested title to the database.
   * Will also add any currently existing episodes.
   */
  public static function addShowWithTitle($title, $allowNonMal, $queue = 'default', $fromJob = false) {
    // Handle job related tasks
    if (!handleJobFunction('ShowAdd', $title, null, $fromJob)) return Show::withTitle($title)->first();

    // Confirm this show isn't already in our databse
    $dbshow = Show::withTitle($title)->first();
    if (Self::handleExistingShow($dbshow, $queue)) return $dbshow;

    // Try to find this show on MAL and get it's id
    $mal_id = MyAnimeList::getMalIdForTitle($title);

    if (isset($mal_id)) {
      // Create and return a new show with the proper data
      return Self::addShowWithMalId($mal_id, $queue, $fromJob);
    }

    elseif ($allowNonMal) {
      // Create a mostly empty show because we don't have MAL data
      $show = Self::updateShowData(null, [
        'title' => $title,
        'alts' => [$title],
        'description' => 'No Description Available',
      ], true, $queue);
      // Mail an anomaly report
      mailAnomaly($show, 'Could not find show on MAL.', [
        'Ran From a Job' => $fromJob ? 'Yes' : 'No',
      ]);
      // Return the show
      return $show;
    }

    else {
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
    if (Self::handleExistingShow($dbshow, $queue)) return $dbshow;

    // Create a new show with the proper data
    $show = Self::updateShowData(null, MyAnimeList::getAnimeData($mal_id), true, $queue);

    // Return the show
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
    $mode = $episodes ? 'true' : 'false';
    if (!handleJobFunction('ShowUpdate('.$mode.')', $jobShowId, null, $fromJob)) return Show::find($show_id);

    // If this show has dissappeard from MAL, reset the MAL id
    if (isset($show->mal_id) && !$show->mal_linked) {
      $show->mal_id = null;
      $show->save();
    }

    // If the mal id is not known yet, try to find it first
    if (!isset($show->mal_id)) {
      $mal_id = MyAnimeList::getMalIdForTitle($show->title);
      if (isset($mal_id)) {
        // Remove all other shows with this mal id
        Show::where('id', '!=', $show->id)->where('mal_id', $mal_id)->delete();
        // Update this show
        Self::updateShowData($show, MyAnimeList::getAnimeData($mal_id), true, $queue);
      }
    }

    // Otherwise just update the cache
    else {
      Self::updateShowData($show, MyAnimeList::getAnimeData($show->mal_id), $episodes, $queue);
    }

    // Return the show object
    return $show;
  }

  /**
   * Delete old thumbnails for this show and download new ones.
   */
  private static function updateThumbnails($show) {
    // Delete current thumbnails
    foreach ($show->local_thumbnail_ids as $thumbnail_id) {
      try {
        unlink(public_path('media/thumbnails/'.$thumbnail_id));
      } catch (\Exception $e) {}
    }

    // Download new thumbnails
    $thumbnail_ids = [];
    foreach ($show->remote_thumbnail_urls as $index => $remote) {
      $thumbnail_id = $show->id.'-'.($index+1);
      $local = public_path('media/thumbnails/'.$thumbnail_id);
      try {
        unlink($local);
      } catch (\Exception $e) {}
      copy($remote, $local);
      $thumbnail_ids[] = $thumbnail_id;
    }

    // Save new thumbnail ids
    $show->local_thumbnail_ids = $thumbnail_ids;
    $show->save();
  }
}
