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
        // We have an episode overview page now
        $data_stream = [
          'show_id' => $show->id,
          'streamer_id' => 'animeshow',
          'link_stream' => 'http://animeshow.tv/'.str_replace(' ', '-', $alt),
        ];

        // Grab a list of episodes
        $epsiode_list = explode('</div>', Helpers::str_get_between($page, '<div id="episodes_list">', '<div id="sidebar">'));
        // Turn it into an array of episodes
        $episodes = [];
        foreach ($epsiode_list as $line) {
          // Read line with episode number
          if (strpos($line, '<div class="episodes_list_hover">') !== false) {
            $episode_num = Helpers::str_get_between($line, 'Episode ');
            $episodes[] = [
              'episode_num' => $episode_num,
              'link_episode' => 'http://animeshow.tv/'.str_replace(' ', '-', $alt).'-episode-'.$episode_num,
            ];
          }
          // Read line with upload date
          if (strpos($line, '<div class="col-lg-2 col-md-3 hidden-sm hidden-xs">') !== false) {
            $episodes[count($episodes) - 1]['uploadtime'] = Carbon::createFromFormat('d M Y', Helpers::str_get_between($line, '<div class="col-lg-2 col-md-3 hidden-sm hidden-xs">'))
                                                                    ->hour(0)->minute(0)->second(0);
          }
        }

        // Episode list done, start grabbing video data
        foreach ($episodes as $episode) {
          $page = file_get_contents($episode['link_episode']);
          // Grab a list of mirrors
          $mirror_list = explode('</div>', Helpers::str_get_between($page, '<div id="episode_mirrors">', '<br />')); //let's hope this goes well
          // Turn it into an array of mirrors
          $mirrors = [[
            'link_video' => $episode['link_episode'],
            'resolution' => '1280x720',
          ]];
          foreach ($mirror_list as $line) {
            // Read line with animeshow link
            if (strpos($line, '<a href="') !== false) {
              $mirrors[] = [
                'link_video' => Helpers::str_get_between($line, '<a href="', '/"'),
                'resolution' => '1280x720',
              ];
            }
            // Read line with translation type
            if (strpos($line, '<div class="episode_mirrors_type_') !== false) {
              $mirrors[count($mirrors) - 1]['translation_type'] = Helpers::str_get_between($line, '<div class="episode_mirrors_type_', '"');
            }
            // Read line with hd flag
            if (strpos($line, 'class="glyphicon glyphicon-hd-video"') !== false) {
              $mirrors[count($mirrors) - 1]['resolution'] = '1920x1080';
            }
          }

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
