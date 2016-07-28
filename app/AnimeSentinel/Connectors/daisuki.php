<?php

namespace App\AnimeSentinel\Connectors;

use App\Video;
use App\AnimeSentinel\Helpers;

class daisuki
{
  /**
   * Finds all video's for the requested show.
   * Returns data as an array of models.
   *
   * @return array
   */
  public static function seek($show) {
    return [];
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
    return [];
  }
}
