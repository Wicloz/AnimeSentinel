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
  public static function seek($show, $req_episode_num = null) {
    $videos = [];
    $processedLinks = [];

    // Try all alts to get a valid episode page
    foreach ($show->alts as $alt) {
      // Download search results page
      $page = Downloaders::downloadPage('http://kissanime.to/Search/Anime?keyword='.str_replace(' ', '+', $alt));

      // First check whether we already have an episode page
      if (str_contains($page, '<meta name="description" content="Watch online and download ')) {
        $link_stream = str_get_between($page, '<a Class="bigChar" href="', '">');
        if (!in_array($link_stream, $processedLinks)) {
          // Search for videos
          $videos = array_merge($videos, Self::seekEpisodes($page, $show, $alt, [
            'link_stream' => 'http://kissanime.to'.$link_stream,
            'translation_type' => (str_contains(str_get_between($page, '<title>', '</title>'), '(Dub)') ? 'dub' : 'sub'),
          ], $req_episode_num));
          $processedLinks[] = $link_stream;
          continue;
        }
      }

      // Otherwise, scrape and process search results
      $results = Helpers::scrape_page(str_get_between($page, '<tr style="height: 10px">', '</table>'), '</tr>', [
        'link_stream' => [true, '<a class="bigChar" href="', '">'],
        'title' => [false, '<a class="bigChar" href="{{link_stream}}">', '<'],
      ]);
      foreach ($results as $result) {
        // Determine translation type and clean up title
        $result['translation_type'] = 'sub';
        $result['title'] = trim(str_replace('(Sub)', '', $result['title']));
        if (str_contains($result['title'], '(Dub)')) {
          $result['translation_type'] = 'dub';
        }
        $result['title'] = trim(str_replace('(Dub)', '', $result['title']));

        $matches = false;
        foreach ($show->alts as $alt2) {
          if (match_fuzzy($alt2, $result['title'])) {
            $matches = true;
            $alt = $alt2;
            break;
          }
        }
        if ($matches && !in_array($result['link_stream'], $processedLinks)) {
          // Search for videos
          $page = Downloaders::downloadPage('http://kissanime.to'.$result['link_stream']);
          $videos = array_merge($videos, Self::seekEpisodes($page, $show, $alt, [
            'link_stream' => 'http://kissanime.to'.$result['link_stream'],
            'translation_type' => $result['translation_type'],
          ], $req_episode_num));
          $processedLinks[] = $result['link_stream'];
        }
      }
    }

    return $videos;
    // Stream specific:   'show_id', 'streamer_id', 'translation_type', 'link_stream'
    // Episode specific:  'episode_num', 'link_episode', ('notes')
    // Video specific:    'uploadtime', 'link_video', 'resolution'
  }

  private static function seekEpisodes($page, $show, $alt, $data, $req_episode_num) {
    $videos = [];

    // Set some general data
    $data_stream = array_merge($data, [
      'show_id' => $show->id,
      'streamer_id' => 'kissanime',
    ]);
    $alt = trim(str_get_between($page, '<div class="barTitle">', 'information'));

    // Scrape the page for episode data
    $episodes = Helpers::scrape_page(str_get_between($page, '<tr style="height: 10px">', '</table>'), '</td>', [
      'episode_num' => [true, 'in high quality">', '</a>'],
      'link_episode' => [false, 'href="', '"'],
      'uploadtime' => [false, '<td>', ''],
    ]);
    foreach ($episodes as $index => $episode) {
      $episodes[$index]['episode_num'] = trim(str_replace($alt, '', $episode['episode_num']));
    }

    // Find the decrement
    $decrement = Self::findDecrement($episodes);

    // Get mirror data for each episode
    foreach (array_reverse($episodes) as $episode) {
      // Complete episode data
      $episode = Self::seekCompleteEpisode($episode, $decrement);
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
    // Scrape the page for mirror data
    $mirrors = Helpers::scrape_page(str_get_between($page, 'id="divDownload">', '</div>'), '</a>', [
      'link_video' => [true, 'href="', '"'],
      'resolution' => [false, '>', '.'],
    ]);
    return $mirrors;
  }

  private static function seekCompleteEpisode($episode, $decrement) {
    // Complete episode data
    $episode['notes'] = '';
    $episode['episode_num'] = Self::convertEpisodeToNumber($episode['episode_num'], $episode['notes']);

    if ($episode['episode_num'] === false) {
      return false;
    }

    $episode['episode_num'] -= $decrement;
    $episode['notes'] = trim(str_replace('[', '(', str_replace(']', ')', $episode['notes'])));
    $episode['link_episode'] = 'http://kissanime.to'.$episode['link_episode'];
    $episode['uploadtime'] = Carbon::createFromFormat('n/j/Y', trim($episode['uploadtime']));
    return $episode;
  }

  private static function seekCompleteMirror($mirror) {
    // Complete mirror data
    return $mirror;
  }

  private static function convertEpisodeToNumber($text, & $notes = '') {
    $text = trim(str_replace('(Sub)', '', str_replace('(Dub)', '', $text)));

    // Discard ops, eds and previews
    if (str_starts_with($text, '_OP') || str_starts_with($text, '_ED') || str_starts_with($text, '_Preview')) {
      return false;
    }
    else {
      $special = false;
      // Handle numbered specials
      if (str_starts_with($text, '_Special')) {
        $special = true;
      }
      // Strip '_' and type names from the start of the text, followed by trimming
      $remove = [
        '_',
        'Episode',
        'Movie',
        'Special',
        'ONA',
        'OVA',
      ];
      foreach ($remove as $item) {
        if (str_starts_with($text, $item)) {
          $text = trim(str_replace_first($item, '', $text));
        }
      }
      if (!$special) {
        // Try to get the episode number
        $episode_num = str_get_between('-'.$text, '-', ' ');
        if ($episode_num === false) {
          $episode_num = str_get_between('-'.$text, '-', '');
        }
        // If that's not a number, set it to 1
        if (!is_numeric($episode_num)) {
          $episode_num = 1;
        }
        // Strip numbers from the start of the text and trim, set that as the notes
        $notes = trim(preg_replace('/^[0-9]+/u', '', $text));
      }
      else {
        $episode_num = 0;
        $notes = trim($text);
      }

      return $episode_num;
    }
  }

  private static function findDecrement($episodes) {
    // Find the lowest episode number
    foreach ($episodes as $episode) {
      $episode = Self::seekCompleteEpisode($episode, 0);
      if (!empty($episode) && (!isset($lowest_ep) || $episode['episode_num'] < $lowest_ep)) {
        $lowest_ep = $episode['episode_num'];
      }
    }
    if (!isset($lowest_ep) || $lowest_ep <= 0) {
      return 0;
    }
    return $lowest_ep - 1;
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
    $page = Downloaders::downloadPage('http://kissanime.to');

    // Scrape the 'recently aired' page
    $dataRaw = Helpers::scrape_page(str_get_between($page, '<div class="items">', '<div class="clear">'), '</a>', [
      'link_stream' => [true, 'href="', '"'],
      'title' => [false, '<br />', '<br />'],
      'episode_num' => [false, '<span class=\'textDark\'>', '</span>'],
    ]);

    // Complete and return data
    $data = [];
    foreach ($dataRaw as $item) {
      // Determine translation type and clean up title
      $item['translation_type'] = 'sub';
      $item['title'] = trim(str_replace('(Sub)', '', $item['title']));
      if (str_contains($item['title'], '(Dub)')) {
        $item['translation_type'] = 'dub';
      }
      $item['title'] = trim(str_replace('(Dub)', '', $item['title']));

      // Determine the decrement
      $page = Downloaders::downloadPage('http://kissanime.to/'.$item['link_stream']);
      $alt = trim(str_get_between($page, '<div class="barTitle">', 'information'));
      // Scrape the page for episode data
      $episodes = Helpers::scrape_page(str_get_between($page, '<tr style="height: 10px">', '</table>'), '</td>', [
        'episode_num' => [true, 'in high quality">', '</a>'],
        'link_episode' => [false, 'href="', '"'],
        'uploadtime' => [false, '<td>', ''],
      ]);
      foreach ($episodes as $index => $episode) {
        $episodes[$index]['episode_num'] = trim(str_replace($alt, '', $episode['episode_num']));
      }
      $decrement = Self::findDecrement($episodes);
      // Determine actual episode number
      $item['episode_num'] = Self::convertEpisodeToNumber($item['episode_num']) - $decrement;

      if (!empty($item)) {
        $data[] = $item;
      }
    }

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
    // Complete mirror data and return video link
    $mirror = Self::seekCompleteMirror($mirrors[$video->mirror - 1]);
    return $mirror['link_video'];

    return $video->link_video;
  }
}
