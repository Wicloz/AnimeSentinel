<?php

namespace App\AnimeSentinel\Connectors;

use App\Video;
use App\AnimeSentinel\Helpers;
use App\AnimeSentinel\Downloaders;

class template
{
  /**
   * Finds all video's for the requested show.
   * Returns data as an array of models.
   *
   * @return array
   */
  public static function seek($show, $req_episode_num = null) {
    // Try all alts to get a valid episode page
    foreach ($show->alts as $alt) {
      $page = Downloaders::downloadPage('TODO');
      if (strpos($page, 'TODO') !== false) {
        return Self::seekEpisodes($page, $show, $alt, [], $req_episode_num);
      }
    }

    return [];
    // Stream specific:   'show_id', 'streamer_id', 'translation_type', 'link_stream'
    // Episode specific:  'episode_num', 'link_episode', ('notes')
    // Video specific:    'uploadtime', 'link_video', 'resolution'
  }

  private static function seekEpisodes($page, $show, $alt, $data, $req_episode_num) {
    $videos = [];

    // Set some general data
    $data_stream = array_merge($data, [
      'show_id' => $show->id,
      'streamer_id' => 'TODO',
    ]);

    // Scrape the page for episode data
    $episodes = Helpers::scrape_page(str_get_between($page, 'TODO', 'TODO'), 'TODO', [
      'TODO' => [true, 'TODO', 'TODO'],
    ]);

    // Get mirror data for each episode
    foreach (array_reverse($episodes) as $episode) {
      // Complete episode data
      $episode = Self::seekCompleteEpisode($episode);
      if (empty($episode)) {
        continue;
      }

      elseif ($req_episode_num === null || $req_episode_num === (int) $episode['episode_num']) {
        // Get all mirrors data
        $mirrors = Self::seekMirrors($episode['link_episode']);
        // Loop through mirror list
        foreach ($mirrors as $mirror) {
          // Complete mirror data
          $mirror = Self::seekCompleteMirror($mirror);
          // Create and add final video
          $videos[] = new Video(array_merge($data_stream, $episode, $mirror));
        }
      }
    }

    return $videos;
  }

  private static function seekMirrors($link_episode) {
    // Get episode page
    $page = Downloaders::downloadPage($link_episode);
    // Scrape the page for mirror data
    $mirrors = Helpers::scrape_page(str_get_between($page, 'TODO', 'TODO'), 'TODO', [
      'TODO' => [true, 'TODO', 'TODO'],
    ]);
    return $mirrors;
  }

  private static function seekCompleteEpisode($episode) {
    // Complete episode data
    //TODO
    return $episode;
  }

  private static function seekCompleteMirror($mirror) {
    // Complete mirror data
    //TODO
    return $mirror;
  }

  /**
   * Finds all episode data + title from the recently aired page.
   * Returns this data as an array of associative arrays.
   * This data is later used to find the episode video's.
   *
   * @return array
   */
  public static function guard() {
    // Download the 'recently aired' page
    $page = Downloaders::downloadPage('TODO');

    // Scrape the 'recently aired' page
    $data = Helpers::scrape_page(str_get_between($page, 'TODO', 'TODO'), 'TODO', [
      'TODO' => [true, 'TODO', 'TODO'],
    ]);

    // Complete and return data
    foreach ($data as $index => $item) {
      //TODO
      $data[$index] = $item;
    }

    return $data;
    // Contains: title, episode_num, (translation_type)
  }

  /**
   * Finds the stream link for the requested video.
   *
   * @return string
   */
  public static function findVideoLink($video) {
    // Get all mirrors data
    $mirrors = Self::seekMirrors($video->link_episode);

    // Loop through mirror list
    foreach ($mirrors as $mirror) {
      // Complete mirror data
      $mirror = Self::seekCompleteMirror($mirror);
      // Determine which link to return (TODO)
      if ($mirror['resolution'] === $video->resolution) {
        return $mirror['link_video'];
      }
    }

    return $video->link_video;
  }
}
