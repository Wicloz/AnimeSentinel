<?php

namespace App\AnimeSentinel;

use Carbon\Carbon;
use App\Show;

class MyAnimeList
{
  /**
   * Does an API search on MAL with the requested query.
   * Converts the results to an array of stdClass.
   *
   * @return array
   */
  public static function searchApi($query) {
    // Preform curl request
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, 'https://myanimelist.net/api/anime/search.xml?q='.str_replace(' ', '+', $query));
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_USERNAME, config('animesentinel.mal_username'));
    curl_setopt($curl, CURLOPT_PASSWORD, config('animesentinel.mal_password'));
    $response = curl_exec($curl);
    curl_close($curl);
    // Convert to xml
    $xml = simplexml_load_string($response);
    // Convert xml to results
    if (empty($xml)) return [];
    $results = [];
    foreach ($xml as $entry) {
      $result = json_decode(json_encode($entry));
      if (!is_string($result->english)) $result->english = '';
      $json_synonyms = json_encode($result->synonyms);
      if ($json_synonyms !== '{}') {
        $result->synonyms = explode('; ', $json_synonyms);
      } else {
        $result->synonyms = [];
      }
      $result->mal = true;
      $results[] = $result;
    }
    return $results;
  }

  /**
   * Does a regular search on MAL with the requested query.
   *
   * @return array
   */
  public static function search($query) {
    $page = Downloaders::downloadPage('https://myanimelist.net/anime.php?q='.str_replace(' ', '+', $query).'&type=0&score=0&status=0&p=0&r=0&sm=0&sd=0&sy=0&em=0&ed=0&ey=0&c[]=a&c[]=b&c[]=d&c[]=e&c[]=g&gx=1&genre[]=12');
    $shows = array_slice(Helpers::scrape_page(str_get_between($page, '</div>Search Results</div>', '</table>'), '</tr>', [
      'mal_id' => [true, 'https://myanimelist.net/anime/', '/'],
      'thumbnail_id' => [false, '/images/anime/', '?'],
      'title' => [false, '<strong>', '</strong>'],
      'description' => [false, '<div class="pt4">', '</div>'],
      'type' => [false, 'width="45">', '</td>'],
      'episode_amount' => [false, 'width="40">', '</td>'],
      'airing' => [false, 'width="80">', 'width="75">'],
      'rating' => [false, 'width="75">', '</td>'],
    ]), 0, 128);

    $results = collect([]);
    foreach ($shows as $show) {
      $result = new Show();
      $result->mal = true;

      $result->mal_id = $show['mal_id'];
      $result->title = $show['title'];

      $result->type = strtolower(trim($show['type']));
      if ($result->type === '-') {
        $result->type = null;
      }

      $result->episode_amount = trim($show['episode_amount']);
      if ($result->episode_amount === '-') {
        $result->episode_amount = null;
      }

      if (str_contains($show['description'], '</a>')) {
        $result->description = str_get_between($show['description'], '', '<a');
      } else {
        $result->description = $show['description'];
      }

      $airing = explode('</td>', $show['airing']);
      $result->airing_start = Self::convertSearchAiringToCarbon(trim($airing[0]));
      $result->airing_end = Self::convertSearchAiringToCarbon(trim(str_get_between($airing[1], 'width="80">')));

      $result->rating = trim($show['rating']);
      if ($result->rating === '-') {
        $result->rating = null;
      }

      if (!empty($show['thumbnail_id'])) {
        $result->remote_thumbnail_urls = ['https://myanimelist.cdn-dena.com/images/anime/'.$show['thumbnail_id']];
      }

      $results[] = $result;
    }
    return $results;
  }

  /**
   * Tries to find the mal id for the requested title.
   * Returns null if it cannot be found.
   *
   * @return integer
   */
  public static function getMalIdForTitle($title) {
    // First try searching using the api
    $results = Self::searchApi($title);
    foreach ($results as $result) {
      // Create alts list
      $alts[] = $result->title;
      $alts[] = $result->english;
      $alts = Helpers::mergeFlagAlts(array_merge($alts, $result->synonyms), $result->id);
      // Check for a title match
      foreach ($alts as $alt) {
        if (match_fuzzy($alt, $title)) {
          return $result->id;
        }
      }
    }

    // If that fails, try using the regular search
    $page = Downloaders::downloadPage('https://myanimelist.net/anime.php?q='.str_replace(' ', '+', $title).'&gx=1&genre[]=12');
    $shows = array_slice(Helpers::scrape_page(str_get_between($page, '</div>Search Results</div>', '</table>'), '</tr>', [
      'mal_id' => [true, 'https://myanimelist.net/anime/', '/'],
      'type' => [false, 'width="45">', '</td>'],
    ]), 0, 8);
    foreach ($shows as $show) {
      // Get MAL data
      $data = Self::getAnimeData($show['mal_id']);
      // Check for a title match
      foreach ($data['alts'] as $alt) {
        if (match_fuzzy($alt, $title)) {
          return $show['mal_id'];
        }
      }
    }

    return null;
  }

  /**
   * Scrapes all important data from MAL for the anime with the requested id.
   *
   * @return array
   */
  public static function getAnimeData($mal_id) {
    $page = Downloaders::downloadPage('https://myanimelist.net/anime/'.$mal_id);
    $pictures = Downloaders::downloadPage('https://myanimelist.net/anime/'.$mal_id.'/text/pics');

    $title = trim(str_get_between($page, '<span itemprop="name">', '</span>'));

    $alts[] = $title;
    $set = explode('</div>', str_get_between($page, '<h2>Alternative Titles</h2>', '<br />'));
    foreach ($set as $item) {
      if (trim($item) !== '' && !str_contains($item, '<span class="dark_text">Japanese:</span>')) {
        $list = trim(str_get_between($item, '</span>'));
        $alts = array_merge($alts, explode(', ', $list));
      }
    }

    $episode_amount = str_get_between($page, '<span class="dark_text">Episodes:</span>', '</div>');
    settype($episode_amount, 'int');
    if ($episode_amount === 0) $episode_amount = null;

    $durations = explode('hr.', trim(str_get_between($page, '<span class="dark_text">Duration:</span>', '</div>')));
    $episode_duration = 0;
    if (count($durations) === 1) {
      $episode_duration += (double) $durations[0];
    } else if (count($durations) === 2) {
      $episode_duration += (double) $durations[0] * 60;
      $episode_duration += (double) $durations[1];
    }
    if ($episode_duration === 0) $episode_duration = null;

    $genres = [];
    $set = explode('</a>', str_get_between($page, '<span class="dark_text">Genres:</span>', '</div>'));
    foreach ($set as $item) {
      if (trim($item) !== '' && trim($item) !== 'No genres have been added yet.') {
        $genres[] = strtolower(trim(str_get_between($item, '>')));
      }
    }

    $description = trim(str_get_between($page, '<span itemprop="description">', '<h2 style="margin-top: 15px;">'));
    if (str_ends_with($description, '</span>')) {
      $description = trim(str_replace_last('</span>', '', $description));
    }

    $type = strtolower(trim(str_get_between(str_get_between($page, '<span class="dark_text">Type:</span>', '</div>'), '>', '</a>')));
    if (empty($type)) {
      $type = null;
    }

    $season = strtolower(trim(str_get_between(str_get_between($page, '<span class="dark_text">Premiered:</span>', '</div>'), '>', '</a>')));
    if (empty($season)) {
      $season = null;
    }

    $rating = trim(str_get_between(str_get_between($page, '<span class="dark_text">Rating:</span>', '</div>'), '', ' - '));
    if (empty($rating)) {
      $rating = null;
    }

    $broadcast = trim(str_get_between($page, '<span class="dark_text">Broadcast:</span>', '</div>'));
    if (str_contains($broadcast, ' at ')) {
      $airing_type = 'weekly';
    } elseif (mb_strtolower($broadcast) === 'not scheduled once per week') {
      $airing_type = 'irregular';
    } else {
      $airing_type = null;
    }

    $airing_time = str_get_between($broadcast, 'at ', ' (JST)');
    if (empty($airing_time)) {
      $airing_time = null;
    } else {
      $airing_time = Carbon::createFromFormat('H:i', $airing_time, 'JST');
    }

    $airing_start = null;
    $airing_end = null;
    $aired = trim(str_get_between($page, '<span class="dark_text">Aired:</span>', '</div>'));
    if ($aired !== 'Not available') {
      $aired = explode(' to ', $aired);
      if ($aired[0] !== '?') {
        $airing_start = Self::convertDetailsAiringToCarbon($aired[0], $airing_time);
      }
      if (count($aired) === 1) {
        $airing_end = $airing_start;
      } elseif ($aired[1] !== '?') {
        $airing_end = Self::convertDetailsAiringToCarbon($aired[1], $airing_time);
      }
    }

    if ($airing_time !== null) {
      $airing_time->tz('UTC');
    }

    $prequels = [];
    $sequels = [];
    $summaries = [];
    $specials = [];
    $alternatives = [];
    $others = [];

    $set = explode('<tr>', str_get_between($page, '<table class="anime_detail_related_anime" style="border-spacing:0px;">', '</table>'));
    foreach ($set as $item) {
      if ($item !== '') {
        $kind = mb_strtolower(str_get_between($item, '<td nowrap="" valign="top" class="ar fw-n borderClass">', '</td>'));
        $links = explode('</a>', str_get_between($item, '<td width="100%" class="borderClass">', '</td>'));
        foreach ($links as $link) {
          if ($link !== '') {
            $data = [
              'mal_id' => str_get_between($link, '/anime/', '/'),
              'title' => str_get_between($link, '>', ''),
            ];
            switch ($kind) {
              case 'prequel:':
                $prequels[] = $data;
              break;
              case 'sequel:':
                $sequels[] = $data;
              break;
              case 'summary:':
                $summaries[] = $data;
              break;
              case 'side story:':
                $specials[] = $data;
              break;
              case 'alternative version:':
                $alternatives[] = $data;
              break;
              case 'other:':
                $others[] = $data;
              break;
            }
          }
        }
      }
    }

    $primary_thumbnail_id = str_get_between($page, '/images/anime/', '"');
    $secondary_thumbnails = Helpers::scrape_page(str_get_between($pictures, '<table border="0" cellpadding="0" cellspacing="10" align="center">', '</table>'), '</td>', [
      'thumbnail_id' => [true, '/images/anime/', '"'],
    ]);
    $remote_thumbnail_urls = collect(['https://myanimelist.cdn-dena.com/images/anime/'.$primary_thumbnail_id]);
    foreach ($secondary_thumbnails as $thumbnail) {
      $thumbnail_url = 'https://myanimelist.cdn-dena.com/images/anime/'.$thumbnail['thumbnail_id'];
      if (!$remote_thumbnail_urls->contains($thumbnail_url)) {
        $remote_thumbnail_urls[] = $thumbnail_url;
      }
    }

    return [
      'mal_id' => $mal_id,
      'remote_thumbnail_urls' => $remote_thumbnail_urls,
      'title' => $title,
      'alts' => Helpers::mergeFlagAlts($alts, $mal_id),
      'description' => $description,
      'prequels' => $prequels,
      'sequels' => $sequels,
      'summaries' => $summaries,
      'specials' => $specials,
      'alternatives' => $alternatives,
      'others' => $others,
      'type' => $type,
      'genres' => $genres,
      'episode_amount' => $episode_amount,
      'episode_duration' => $episode_duration,
      'season' => $season,
      'rating' => $rating,
      'airing_start' => $airing_start,
      'airing_end' => $airing_end,
      'airing_time' => $airing_time,
      'airing_type' => $airing_type,
    ];
  }

  private static function convertDetailsAiringToCarbon($dateString, $time) {
    $carbon = null;

    if (count(explode(' ', $dateString)) === 3) {
      $carbon = Carbon::createFromFormat('M j, Y', $dateString, 'JST')->setTime(12, 0, 0);
    }
    if (count(explode(' ', $dateString)) === 2) {
      $carbon = Carbon::createFromFormat('M, Y', $dateString, 'JST')->day(1)->setTime(12, 0, 0);
    }
    if (count(explode(' ', $dateString)) === 1) {
      $carbon = Carbon::createFromFormat('Y', $dateString, 'JST')->month(1)->day(1)->setTime(12, 0, 0);
    }

    if ($carbon !== null && $time !== null) {
      $carbon->setTime($time->hour, $time->minute, $time->second)->tz('UTC');
    } else {
      $carbon->tz('UTC')->setTime(0, 0, 0);
    }

    return $carbon;
  }

  private static function convertSearchAiringToCarbon($string) {
    $bits = explode('-', $string);
    if (count($bits) === 3) {
      foreach ($bits as $index => $bit) {
        if (str_contains($bit, '?')) {
          $bits[$index] = 1;
        }
      }
      $year = Carbon::createFromFormat('y', $bits[2], 'JST');
      $carbon = Carbon::createFromDate($year->year, $bits[0], $bits[1], 'JST')->setTime(12, 0, 0);
      return $carbon->tz('UTC')->setTime(0, 0, 0);
    }
    return null;
  }
}
