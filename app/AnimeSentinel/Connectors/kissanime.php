<?php

namespace App\AnimeSentinel\Connectors;

use App\Video;
use App\AnimeSentinel\Helpers;
use App\AnimeSentinel\Downloaders;
use Carbon\Carbon;

class kissanime
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
      $page = Downloaders::downloadCloudFlare('http://kissanime.to/Anime/'.str_urlify($alt), 'kissanime');
      if (strpos($page, '<meta name="description" content="Watch online and download ') !== false) {
        $videos = array_merge($videos, Self::seekEpisodes($page, $show, 'http://kissanime.to/Anime/'.str_urlify($alt), 'sub'));
      }

      $page = Downloaders::downloadCloudFlare('http://kissanime.to/Anime/'.str_urlify($alt).'-dub', 'kissanime');
      if (strpos($page, '<meta name="description" content="Watch online and download ') !== false) {
        $videos = array_merge($videos, Self::seekEpisodes($page, $show, 'http://kissanime.to/Anime/'.str_urlify($alt).'-dub', 'dub'));
      }
    }

    return $videos;
    // Stream specific:   'show_id', 'streamer_id', 'translation_type', 'link_stream'
    // Episode specific:  'episode_num', 'link_episode', ('notes')
    // Video specific:    'uploadtime', 'link_video', 'resolution'
  }

  private static function seekEpisodes($page, $show, $link_stream, $translation_type) {
    $videos = [];

    // We have an episode overview page now, so set some general data
    $data_stream = [
      'show_id' => $show->id,
      'streamer_id' => 'kissanime',
      'translation_type' => $translation_type,
      'link_stream' => $link_stream,
    ];

    // Scrape the page for episode data
    $episodes = Helpers::scrape_page(str_get_between($page, '<tr style="height: 10px">', '</table>'), '</td>', [
      'episode_num' => [true, 'Watch anime ', ' in high quality'],
      'link_episode' => [false, 'href="', '"'],
      'uploadtime' => [false, '<td>', ''],
    ]);

    // Get mirror data for each episode
    foreach ($episodes as $episode) {
      // Complete episode data
      if (strpos($episode['link_episode'], '/Episode-') !== false) {
        $line = $episode['episode_num'];
        $episode['episode_num'] = str_get_between($line, 'Episode ', ' ');
        $episode['notes'] = str_get_between($line, 'Episode '.$episode['episode_num'].' ', ' online');
      }
      elseif (strpos($episode['link_episode'], '/Movie?') !== false) {
        $episode['notes'] = str_get_between($episode['episode_num'], 'Movie ', ' online', true);
        $episode['episode_num'] = 1;
      }
      else {
        continue;
      }
      $episode['notes'] = str_replace('[', '(', str_replace(']', ')', $episode['notes']));
      $episode['link_episode'] = 'http://kissanime.to'.$episode['link_episode'];
      $episode['uploadtime'] = Carbon::createFromFormat('n/j/Y', trim($episode['uploadtime']))->hour(12)->minute(0)->second(0);

      // Get episode page
      $page = Downloaders::downloadCloudFlare($episode['link_episode'], 'kissanime');

      // Scrape the page for mirror data
      $mirrors = Helpers::scrape_page(str_get_between($page, 'id="divDownload">', '</div>'), '</a>', [
        'link_video' => [true, 'href="', '"'],
        'resolution' => [false, '>', '.mp4'],
      ]);

      // Loop through mirror list
      foreach ($mirrors as $mirror) {
        // Create and add final video
        $videos[] = new Video(array_merge($data_stream, $episode, $mirror));
      }
    }

    return $videos;
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
    $page = Downloaders::downloadCloudFlare($video->link_episode, 'kissanime');
    $mirrors = Helpers::scrape_page(str_get_between($page, 'id="divDownload">', '</div>'), '</a>', [
      'link_video' => [true, 'href="', '"'],
      'resolution' => [false, '>', '.mp4'],
    ]);

    foreach ($mirrors as $mirror) {
      if ($mirror['resolution'] === $video->resolution) {
        return $mirror['link_video'];
      }
    }

    return $video->link_video;
  }
}
