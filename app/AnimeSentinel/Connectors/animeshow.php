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
  public static function seek($show) {
    // Try all alts to get a valid episode page
    foreach ($show->alts as $alt) {
      $page = file_get_contents('http://animeshow.tv/'.str_urlify($alt));
      if (strpos($page, 'episodes online in high quality with professional English subtitles on AnimeShow.tv"/>') !== false) {
        return Self::seekEpisodes($page, $show, $alt, []);
      }
    }

    // Stream specific:   'show_id', 'streamer_id', 'translation_type', 'link_stream'
    // Episode specific:  'episode_num', 'link_episode', ('notes')
    // Video specific:    'uploadtime', 'link_video', 'resolution'
  }

  private static function seekEpisodes($page, $show, $alt, $data) {
    $videos = [];

    // Set some general data
    $data_stream = array_merge($data, [
      'show_id' => $show->id,
      'streamer_id' => 'animeshow',
      'link_stream' => 'http://animeshow.tv/'.str_urlify($alt),
    ]);

    // Scrape the page for episode data
    $episodes = Helpers::scrape_page(str_get_between($page, '<div id="episodes_list">', '<div id="sidebar">'), '</div>', [
      'episode_num' => [true, 'Episode ', ''],
      'uploadtime' => [false, '<div class="col-lg-2 col-md-3 hidden-sm hidden-xs">', ''],
    ]);

    // Get mirror data for each episode
    foreach ($episodes as $episode) {
      // Complete episode data
      $episode = Self::seekCompleteEpisode($episode, $alt);
      if (empty($episode)) {
        continue;
      }

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

    return $videos;
  }

  private static function seekMirrors($link_episode) {
    // Get episode page
    $page = file_get_contents($link_episode);
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

  private static function seekCompleteEpisode($episode, $alt) {
    // Complete episode data
    $episode['link_episode'] = 'http://animeshow.tv/'.str_urlify($alt).'-episode-'.$episode['episode_num'];
    $episode['uploadtime'] = Carbon::createFromFormat('d M Y', $episode['uploadtime'])->hour(12)->minute(0)->second(0);
    return $episode;
  }

  private static function seekCompleteMirror($mirror) {
    // Complete mirror data
    $page = file_get_contents($mirror['link_video']);
    $mirror['link_video'] = str_get_between($page, '<iframe width="100%" height="100%" id="video_embed" scrolling="no" src="', '"');
    $page = file_get_contents($mirror['link_video']);
    // Grab source link depending on mirror site
    if (strpos($mirror['link_video'], 'mp4upload') !== false) {
      $mirror['link_video'] = str_get_between($page, '"file": "', '"');
    }
    if (strpos($mirror['link_video'], 'auengine') !== false) {
      $mirror['link_video'] = str_get_between($page, 'var video_link = \'', '\';');
    }
    return $mirror;
  }

  /**
   * Finds all video's from the recently aired page.
   * Returns data as an array of models.
   *
   * @return array
   */
  public static function guard() {
    $videos = [];
    //TODO
    return $videos;
  }

  /**
   * Finds the stream link for the requested video.
   *
   * @return string
   */
  public static function videoLink($video) {
    // Get all mirrors data
    $mirrors = Self::seekMirrors($video->link_episode);

    // Loop through mirror list
    foreach ($mirrors as $mirror) {
      // Complete mirror data
      $mirror = Self::seekCompleteMirror($mirror);
      // Determine which link to return
      if ($mirror['resolution'] === $video->resolution && $mirror['translation_type'] === $video->translation_type) {
        return $mirror['link_video'];
      }
    }

    return $video->link_video;
  }
}
