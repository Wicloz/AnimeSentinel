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
  public static function seek($show) {
    $videos = [];
    //TODO
    return $videos;
    // Stream specific:   'show_id', 'streamer_id', 'translation_type', 'link_stream'
    // Episode specific:  'episode_num', 'link_episode', ('notes')
    // Video specific:    'uploadtime', 'link_video', 'resolution'
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
    //TODO
    return $video->link_video;
  }
}
