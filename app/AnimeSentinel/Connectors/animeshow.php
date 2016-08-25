<?php

namespace App\AnimeSentinel\Connectors;

use App\Video;
use App\AnimeSentinel\Helpers;
use App\AnimeSentinel\Downloaders;
use Carbon\Carbon;

class animeshow
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
      $page = Downloaders::downloadPage('http://animeshow.tv/'.str_slugify($alt));
      if (str_contains($page, 'episodes online in high quality with professional English subtitles on AnimeShow.tv"/>')) {
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
      'streamer_id' => 'animeshow',
      'link_stream' => 'http://animeshow.tv/'.str_slugify($alt),
    ]);

    // Scrape the page for episode data
    $episodes = Helpers::scrape_page(str_get_between($page, '<div id="episodes_list">', '<div id="sidebar">'), '</div>', [
      'episode_num' => [true, 'Episode ', ''],
      'uploadtime' => [false, '<div class="col-lg-2 col-md-3 hidden-sm hidden-xs">', ''],
    ]);

    // Get mirror data for each episode
    foreach (array_reverse($episodes) as $episode) {
      // Complete episode data
      $episode = Self::seekCompleteEpisode($episode, $alt);
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
    if (!str_contains($page, '<title>Watch Anime - AnimeShow.tv</title>')) {
      // Create first entry
      $mirrors = [[
        'link_video' => $link_episode,
      ]];
      // Scrape the page for mirror data
      $mirrors = Helpers::scrape_page(str_get_between($page, '<div id="episode_mirrors">', '<br />'), '</div>', [
        'link_video' => [true, '<a href="', '/"'],
        'translation_type' => [false, '<div class="episode_mirrors_type_', '"'],
        'resolution' => [false, '1280x720', '1920x1080', 'class="glyphicon glyphicon-hd-video"'],
      ], $mirrors);
      return $mirrors;
    }
    return [];
  }

  private static function seekCompleteEpisode($episode, $alt) {
    // Complete episode data
    $episode['link_episode'] = 'http://animeshow.tv/'.str_slugify($alt).'-episode-'.$episode['episode_num'].'/';
    $episode['uploadtime'] = Carbon::createFromFormat('d M Y', $episode['uploadtime']);
    return $episode;
  }

  private static function seekCompleteMirror($mirror) {
    // Complete mirror data
    $page = Downloaders::downloadPage($mirror['link_video']);
    $mirror['link_video'] = str_get_between($page, '<iframe width="100%" height="100%" id="video_embed" scrolling="no" src="', '"');
    // Grab source link depending on mirror site
    if (str_contains($mirror['link_video'], 'mp4upload')) {
      $page = Downloaders::downloadPage($mirror['link_video']);
      $mirror['link_video'] = str_get_between($page, '"file": "', '"');
    }
    if (str_contains($mirror['link_video'], 'auengine')) {
      $page = Downloaders::downloadPage($mirror['link_video']);
      $mirror['link_video'] = str_get_between($page, 'var video_link = \'', '\';');
    }
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
     $page = Downloaders::downloadPage('http://animeshow.tv/');

     // Scrape the 'recently aired' page
     $data = Helpers::scrape_page(str_get_between($page, '<h1>LATEST ANIME EPISODES</h1>', '<div id="new_anime">'), 'ago</div>', [
       'title' => [true, '<div class="latest_episode_title">', '</div>'],
       'episode_num' => [false, 'Episode ', '</div>'],
     ]);

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
    $index = 0;
    foreach ($mirrors as $mirror) {
      // Complete mirror data
      $mirror = Self::seekCompleteMirror($mirror);
      // If translation types match, increment index
      if ($mirror['translation_type'] === $video->translation_type) {
        $index++;
      }
      // Once index matches the mirror id, return the video link
      if ($index === $video->mirror) {
        return $mirror['link_video'];
      }
    }

    return $video->link_video;
  }
}
