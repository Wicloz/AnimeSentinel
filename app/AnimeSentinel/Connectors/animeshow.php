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
      if (!str_contains($page, Static::$ide_invalidPage)) {
        return [[
          'translation_type' => 'all',
          'link_stream' => $url,
          'page' => $page,
        ]];
      }
    }
    return [];
  }

  protected static function scrapeStreamPage($page) {
    return Helpers::scrape_page(str_get_between($page, '<div id="episodes_list">', '<div id="sidebar">'), '</div>', [
      'link_episode' => [true, 'href="', '"'],
      'episode_num' => [false, 'Episode ', ''],
      'uploadtime' => [false, '<div class="col-lg-2 col-md-3 hidden-sm hidden-xs">', ''],
    ]);
  }

  protected static function completeEpisodeData($episode) {
    $episode['uploadtime'] = Carbon::createFromFormat('j M Y', $episode['uploadtime'])->setTime(0, 0, 0);
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
    if (!str_ends_with($mirror['link_video'], '/')) {
      $mirror['link_video'] = $mirror['link_video'] . '/';
    }
    $mirror['mirror_id'] = $mirror['link_video'];

    $page = Downloaders::downloadPage($mirror['link_video']);
    $mirror['link_video'] = str_get_between($page, '<iframe width="100%" height="100%" id="video_embed" scrolling="no" src="', '"');

    // Grab source link depending on mirror site
    if (str_contains($mirror['link_video'], 'auengine')) {
      $page = Downloaders::downloadPage($mirror['link_video']);
      $mirror['link_video'] = str_get_between($page, 'var video_link = \'', '\';');
    }

    return $mirror;
  }

  protected static function scrapeRecentlyAired($page) {
    return Helpers::scrape_page(str_get_between($page, '<h1>LATEST ANIME EPISODES</h1>', '<div id="new_anime">'), 'ago</div>', [
      'title' => [true, '<div class="latest_episode_title">', '</div>'],
      'episode_num' => [false, 'latest_episode_episode">', '</div>'],
    ]);
  }

  protected static function completeRecentData($element) {
    $element['translation_type'] = 'sub';
    $remove = [
      'Episode',
      'Movie',
      'Special',
      'ONA',
      'OVA',
    ];
    foreach ($remove as $rem) {
      if (str_starts_with($element['episode_num'], $rem)) {
        $element['episode_num'] = trim(str_replace_first($rem, '', $element['episode_num']));
      }
    }
    if ($element['episode_num'] === '') {
      $element['episode_num'] = 1;
    }
    return $element;
  }
}
