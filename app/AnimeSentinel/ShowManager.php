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
  public static function addShowWithTitle($title, $allowNonMal, $queue = 'default', $job) {
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
        'Connection' => $job->db_data['connection'],
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
  public static function addShowWithMalId($mal_id, $queue = 'default', $job) {
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
  public static function updateShowCache($show, $episodes = false, $queue = 'default', $job) {
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
          return $otherShow;
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
      $remote = 'https://myanimelist.cdn-dena.com/images/anime/'.str_replace('-', '/', $show->thumbnail_id);
      $local = __DIR__.'/../../public/media/thumbnails/'.$show->thumbnail_id;
      copy($remote, $local);
    }
  }
}
