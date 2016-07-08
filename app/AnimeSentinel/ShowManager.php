<?php

namespace App\AnimeSentinel;

use App\Show;

class ShowManager
{
  /**
   * Adds the show with the requested title to the database.
   * Will also search for any currently existing episodes.
   */
  public static function addShow($title) {
    // Confirm this show isn't already in our databse.
    if (!empty(Show::withTitle($title)->first())) return; //TODO: set error

    // Find this show on MAL and get it's id
    $mal = MyAnimeList::searchStrict($title);
    if (empty($mal)) return; //TODO: set error
    $mal_id = $mal->id;

    // Create a new show with the proper data.
    $show = Show::create(MyAnimeList::getAnimeData($mal_id));
    Self::updateThumbnail($show); //TODO: asynchrounus

    // Call the function to find existing episodes
    // TODO
  }

  /**
   * Updates the cached database information for the requested show.
   */
  public static function updateShowCache($show) {
    $show->update(MyAnimeList::getAnimeData($show->mal_id));
    Self::updateThumbnail($show); //TODO: asynchrounus
  }

  /**
   * Downloads the show's thumbnail from MAL.
   */
  private static function updateThumbnail($show) {
    $remote = 'http://cdn.myanimelist.net/images/anime/'.str_replace('-', '/', $show->thumbnail_id);
    $local = __DIR__.'/../../public/media/thumbnails/'.$show->thumbnail_id;
    copy($remote, $local);
  }
}
