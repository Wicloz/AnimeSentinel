<?php

namespace App\AnimeSentinel\Connectors;

use App\AnimeSentinel\Helpers;
use App\AnimeSentinel\Downloaders;
use Carbon\Carbon;

class kissanime extends BaseConnector
{
  protected static $useDecrements = true;
  protected static $page_main = 'http://kissanime.ru';
  protected static $page_recent = 'http://kissanime.ru';
  protected static $ide_invalidPage = '<meta name="description" content="Watch anime online in high quality. Free download high quality anime. Various formats from 240p to 720p HD (or even 1080p). HTML5 available for mobile devices" />';

  public static function findStreamPages($show) {
    $streamPages = []; $addedLinks = [];

    foreach ($show->alts as $alt) {
      // Download search results page
      $page = Downloaders::downloadPage(Static::$page_main.'/Search/Anime?keyword='.str_replace(' ', '+', $alt));

      // First check whether we already have an episode page
      if (str_contains($page, '<meta name="description" content="Watch online and download ')) {
        // Grab the partial link
        $linkPart = str_get_between($page, '<a Class="bigChar" href="', '">');
        // Add a stream page if it hasn't been done before
        if (!in_array($linkPart, $addedLinks)) {
          $streamPages[] = [
            'translation_type' => str_contains(str_get_between($page, '<title>', '</title>'), '(Dub)') ? 'dub' : 'sub',
            'link_stream' => Static::$page_main.$linkPart,
            'page' => $page,
          ];
          $addedLinks[] = $linkPart;
        }
      }

      else {
        // Otherwise, scrape and process search results
        $results = Helpers::scrape_page(str_get_between($page, '<tr style="height: 10px">', '</table>'), '</tr>', [
          'linkPart' => [true, '<a class="bigChar" href="', '">'],
          'title' => [false, '<a class="bigChar" href="{{linkPart}}">', '<'],
        ]);
        foreach ($results as $result) {
          // Determine translation type and clean up title
          $result['translation_type'] = str_contains($result['title'], '(Dub)') ? 'dub' : 'sub';
          $result['title'] = trim(str_replace('(Dub)', '', str_replace('(Sub)', '', $result['title'])));

          // Determine whether the title matches one of the alts
          $matches = false;
          foreach ($show->alts as $alt2) {
            if (match_fuzzy($alt2, $result['title'])) {
              $matches = true;
              break;
            }
          }

          // Add a stream page if it hasn't been done before
          if ($matches && !in_array($result['linkPart'], $addedLinks)) {
            $page = Downloaders::downloadPage(Static::$page_main.$result['linkPart']);
            $streamPages[] = [
              'translation_type' => $result['translation_type'],
              'link_stream' => Static::$page_main.$result['linkPart'],
              'page' => $page,
            ];
            $addedLinks[] = $result['linkPart'];
          }
        }
      }
    }

    return $streamPages;
  }

  protected static function scrapeStreamPage($page) {
    $episodes = Helpers::scrape_page(str_get_between($page, '<tr style="height: 10px">', '</table>'), '</td>', [
      'episode_num' => [true, 'online in high quality">', '</a>'],
      'link_episode' => [false, 'href="', '"'],
      'uploadtime' => [false, '<td>', ''],
    ]);

    $title = trim(str_get_between($page, '<div class="barTitle">', 'information'));
    foreach ($episodes as $index => $episode) {
      $episodes[$index]['episode_num'] = trim(str_replace($title, '', $episode['episode_num']));
    }

    return $episodes;
  }

  protected static function completeEpisodeData($episode) {
    $episode['notes'] = Static::extractEpisodeData($episode['episode_num']);
    if ($episode['episode_num'] === false) {
      return null;
    }

    $episode['notes'] = trim(str_replace('[', '(', str_replace(']', ')', $episode['notes'])));
    $episode['link_episode'] = Static::$page_main.$episode['link_episode'];
    $episode['uploadtime'] = Carbon::createFromFormat('n/j/Y', trim($episode['uploadtime']))->setTime(0, 0, 0);

    return $episode;
  }

  protected static function scrapeEpisodePage($page, $link_episode) {
    return Helpers::scrape_page(str_get_between($page, 'id="divDownload">', '</div>'), '</a>', [
      'link_video' => [true, 'href="', '"'],
      'resolution' => [false, '>', '.'],
    ]);
  }

  protected static function completeMirrorData($mirror) {
    if (str_starts_with($mirror['link_video'], 'https://openload.co/')) {
      $mirror['mirror_id'] = 'openload';
      $mirror['resolution'] = '1280×720';
    } else {
      $mirror['mirror_id'] = $mirror['resolution'];
    }
    return $mirror;
  }

  protected static function scrapeRecentlyAired($page) {
    return Helpers::scrape_page(str_get_between($page, '<div class="items">', '<div class="clear">'), '</a>', [
      'link_stream' => [true, 'href="', '"'],
      'title' => [false, '<br />', '<br />'],
      'episode_num' => [false, '<span class=\'textDark\'>', '</span>'],
    ]);
  }

  protected static function completeRecentData($element) {
    $element['translation_type'] = str_contains($element['title'], '(Dub)') ? 'dub' : 'sub';
    $element['title'] = trim(str_replace('(Dub)', '', str_replace('(Sub)', '', $element['title'])));
    Static::extractEpisodeData($element['episode_num']);
    $element['link_stream'] = Static::$page_main.'/'.$element['link_stream'];
    return $element;
  }

  /**
  * Extract the notes and episode number from the episode string.
  *
  * @return string
  */
  private static function extractEpisodeData(& $episode_num) {
    $text = $episode_num;
    $text = trim(str_replace('(Sub)', '', str_replace('(Dub)', '', $text)));

    // Discard ops, eds and previews
    if (str_starts_with($text, '_OP') || str_starts_with($text, '_ED') || str_starts_with($text, '_Preview')) {
      $episode_num = false;
      return false;
    }

    else {
      if (!str_starts_with($text, '_')) {
        // Strip type names from the start of the text, followed by trimming
        $remove = [
          'Episode',
          'Movie',
          'Special',
          'Specail',
          'ONA',
          'OVA',
        ];
        foreach ($remove as $item) {
          if (str_starts_with($text, $item)) {
            $text = trim(str_replace_first($item, '', $text));
          }
        }
        // Try to get the episode number
        $episode_num = str_get_between($text, '', ' ');
        if ($episode_num === false) {
          $episode_num = str_get_between($text, '', '');
        }
        // If that's not a number, set it to 1
        if (!is_numeric($episode_num)) {
          $episode_num = 1;
        }
        // Strip numbers from the start of the text and trim, set that as the notes
        $notes = trim(preg_replace('/^[0-9.]+/u', '', $text));
      }

      else {
        // Handle numbered specials etc.
        $episode_num = -99;
        $notes = str_replace_first('_', '', trim($text));
      }

      return $notes;
    }
  }
}
