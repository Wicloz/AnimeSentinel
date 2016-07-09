<?php

namespace App\AnimeSentinel\Connectors;

use App\Video;
use App\AnimeSentinel\Helpers;
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
    $videos = [];

    foreach ($show->alts as $alt) {
      $page = file_get_contents('http://animeshow.tv/'.str_replace(' ', '-', $alt));
      if (strpos($page, '<title>Watch Anime - AnimeShow.tv</title>') === false) {
        // We have an episode overview page now, so set some general data
        $data_stream = [
          'show_id' => $show->id,
          'streamer_id' => 'animeshow',
          'link_stream' => 'http://animeshow.tv/'.str_replace(' ', '-', $alt),
        ];

        // Scrape the page for episode data
        $episodes = Helpers::scrape_page(Helpers::str_get_between($page, '<div id="episodes_list">', '<div id="sidebar">'), '</div>', [
          'episode_num' => ['Episode ', ''],
          'uploadtime' => ['<div class="col-lg-2 col-md-3 hidden-sm hidden-xs">', ''],
        ]);

        // Get mirror data for each episode
        foreach ($episodes as $episode) {
          // Complete episode data
          $episode['link_episode'] = 'http://animeshow.tv/'.str_replace(' ', '-', $alt).'-episode-'.$episode['episode_num'];
          $episode['uploadtime'] = Carbon::createFromFormat('d M Y', $episode['uploadtime'])->hour(0)->minute(0)->second(0);

          // Get episode page
          $page = file_get_contents($episode['link_episode']);
          // Create first entry
          $mirrors = [[
            'link_video' => $episode['link_episode'],
          ]];
          // Scrape the page for mirror data
          $mirrors = Helpers::scrape_page(Helpers::str_get_between($page, '<div id="episode_mirrors">', '<br />'), '</div>', [
            'link_video' => ['<a href="', '/"'],
            'translation_type' => ['<div class="episode_mirrors_type_', '"'],
            'resolution' => ['1280x720', '1920x1080', 'class="glyphicon glyphicon-hd-video"'],
          ], $mirrors);

          // Loop through mirror list
          foreach ($mirrors as $mirror) {
            $page = file_get_contents($mirror['link_video']);
            $mirror['link_video'] = Helpers::str_get_between($page, '<iframe width="100%" height="100%" id="video_embed" scrolling="no" src="', '"');
            $page = file_get_contents($mirror['link_video']);
            // Grab source link depending on mirror site
            if (strpos($mirror['link_video'], 'mp4upload') !== false) {
              $mirror['link_video'] = Helpers::str_get_between($page, '"file": "', '"');
            }
            if (strpos($mirror['link_video'], 'auengine') !== false) {
              $mirror['link_video'] = Helpers::str_get_between($page, 'var video_link = \'', '\';');
            }
            // Create and add final video
            $videos[] = new Video(array_merge($data_stream, $episode, $mirror));
          }
        }
      }
    }

    return $videos;
    // Stream specific:   'show_id', 'streamer_id', 'translation_type', 'link_stream'
    // Episode specific:  'episode_num', 'link_episode'
    // Video specific:    'uploadtime', 'link_video', 'resolution'
  }

  /**
   * Finds all video's from the recently aired page.
   *
   * @return array
   */
  public static function guard($show) {
    return [];
  }
}
