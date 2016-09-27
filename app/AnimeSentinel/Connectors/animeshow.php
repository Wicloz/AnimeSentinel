<?php

namespace App\AnimeSentinel\Connectors;

use App\AnimeSentinel\Helpers;
use App\AnimeSentinel\Downloaders;
use Carbon\Carbon;

class animeshow extends BaseConnector
{
  protected static $page_main = 'http://animeshow.tv';
  protected static $page_recent = 'http://animeshow.tv';
  protected static $ide_invalidPage = '<title>Watch Anime - AnimeShow.tv</title>';

  public static function findStreamPages($show) {
    // Try all alts to get a valid episode page
    foreach ($show->alts as $alt) {
      $url = Static::$page_main.'/'.str_to_url($alt);
      $page = Downloaders::downloadPage($url);
      if (str_contains($page, 'episodes online in high quality with professional English subtitles on AnimeShow.tv"/>')) {
        return [[
          'translation_type' => 'all',
          'link_stream' => $url,
          'page' => $page,
        ]];
      }
    }
  }

  protected static function scrapeStreamPage($page) {
    return Helpers::scrape_page(str_get_between($page, '<div id="episodes_list">', '<div id="sidebar">'), '</div>', [
      'link_episode' => [true, 'href="', '"'],
      'episode_num' => [false, 'Episode ', ''],
      'uploadtime' => [false, '<div class="col-lg-2 col-md-3 hidden-sm hidden-xs">', ''],
    ]);
  }

  protected static function completeEpisodeData($episode) {
    $episode['uploadtime'] = Carbon::createFromFormat('d M Y', $episode['uploadtime']);
    return $episode;
  }

  protected static function scrapeEpisodePage($page, $link_episode) {
    return Helpers::scrape_page(str_get_between($page, '<div id="episode_mirrors">', '<br />'), '</div>', [
      'link_video' => [true, '<a href="', '/"'],
      'translation_type' => [false, '<div class="episode_mirrors_type_', '"'],
      'resolution' => [false, '1280x720', '1920x1080', 'class="glyphicon glyphicon-hd-video"'],
    ], [[
      'link_video' => $link_episode,
    ]]);
  }

  protected static function completeMirrorData($mirror) {
    $mirror['mirror_id'] = $mirror['link_video'];

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
}
