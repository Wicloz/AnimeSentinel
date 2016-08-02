<?php

namespace App\AnimeSentinel;

use App\MalFlag;
use Carbon\Carbon;

class MyAnimeList
{
  /**
   * Does a search on MAL with the requested query.
   * Returns all results.
   *
   * @return SimpleXMLElement
   */
  private static function searchApiXml($query) {
    // Preform curl request
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, 'http://myanimelist.net/api/anime/search.xml?q='.str_replace(' ', '+', $query));
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_USERNAME, config('animesentinel.mal_username'));
    curl_setopt($curl, CURLOPT_PASSWORD, config('animesentinel.mal_password'));
    $response = curl_exec($curl);
    curl_close($curl);
    // Convert to and return xml
    $xml = simplexml_load_string($response);
    return $xml;
  }

  /**
   * Does a search on MAL with the requested query.
   * Converts the results to an array of stdClass.
   *
   * @return array
   */
  public static function searchApi($query) {
    $xml = Self::searchApiXml($query);
    if (empty($xml)) return [];
    $results = [];
    foreach ($xml as $entry) {
      $flag = MalFlag::firstOrNew(['mal_id' => $entry->id]);
      if (!$flag->exists) {
        $flag->setFlags()->save();
      }
      if (!$flag->flagged) {
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
      $alts = array_merge($alts, $result->synonyms);
      // Check for a match
      foreach ($alts as $alt) {
        if (match_fuzzy($alt, $title)) {
          return $result->id;
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
    $page = Downloaders::downloadPage('http://myanimelist.net/anime/'.$mal_id);

    $title = trim(str_get_between($page, '<span itemprop="name">', '</span>'));

    $alts[] = $title;
    $set = explode('</div>', str_get_between($page, '<h2>Alternative Titles</h2>', '<br />'));
    foreach ($set as $line) {
      if (trim($line) !== '' && strpos($line, '<span class="dark_text">Japanese:</span>') === false) {
        $list = trim(str_get_between($line, '</span>'));
        $alts = array_merge($alts, explode(', ', $list));
      }
    }

    $amount = str_get_between($page, '<span class="dark_text">Episodes:</span>', '</div>');
    settype($amount, 'int');
    if ($amount === 0) $amount = null;

    $durations = explode('hr.', trim(str_get_between($page, '<span class="dark_text">Duration:</span>', '</div>')));
    $duration = 0;
    if (count($durations) === 1) {
      $duration += $durations[0];
    } else if (count($durations) === 2) {
      $duration += $durations[0] * 60;
      $duration += $durations[1];
    }
    if ($duration === 0) $duration = null;

    $genres = [];
    $set = explode('</a>', str_get_between($page, '<span class="dark_text">Genres:</span>', '</div>'));
    foreach ($set as $item) {
      if (trim($item) !== '') {
        $genres[] = strtolower(trim(str_get_between($item, '>')));
      }
    }

    $airing_start = null;
    $airing_end = null;
    $aired = trim(str_get_between($page, '<span class="dark_text">Aired:</span>', '</div>'));
    if ($aired !== 'Not available') {
      $aired = explode(' to ', $aired);
      if ($aired[0] !== '?') {
        $airing_start = Self::convertAiringToCarbon($aired[0]);
      }
      if ($aired[count($aired) - 1] !== '?') {
        $airing_end = Self::convertAiringToCarbon($aired[count($aired) - 1]);
      }
    }

    return [
      'mal_id' => $mal_id,
      'thumbnail_id' => str_replace('/', '-', str_get_between($page, 'src="http://cdn.myanimelist.net/images/anime/', '"')),
      'title' => $title,
      'alts' => Helpers::mergeFlagAlts($alts, $mal_id),
      'description' => trim(str_get_between($page, '<span itemprop="description">', '</span>')),
      'type' => strtolower(trim(str_get_between(str_get_between($page, '<span class="dark_text">Type:</span>', '</a>'), '>'))),
      'genres' => $genres,
      'episode_amount' => $amount,
      'episode_duration' => $duration,
      'airing_start' => $airing_start,
      'airing_end' => $airing_end,
    ];
  }

  private static function convertAiringToCarbon($string) {
    if (count(explode(' ', $string)) === 3) {
      $carbon = Carbon::createFromFormat('M j, Y', $string);
      return $carbon;
    }
    if (count(explode(' ', $string)) === 2) {
      $carbon = Carbon::createFromFormat('M, Y', $string)->day(1);
      return $carbon;
    }
    if (count(explode(' ', $string)) === 1) {
      $carbon = Carbon::createFromFormat('Y', $string)->day(1)->month(1);
      return $carbon;
    }
    return null;
  }

  /**
   * Finds out if the requested anime is hentai or music rather than an anime.
   *
   * @return stdClass
   */
  public static function malFlags($mal_id) {
    $page = Downloaders::downloadPage('http://myanimelist.net/anime/'.$mal_id);
    $flags = (object) ['hentai' => false];

    $flags->music = strtolower(trim(str_get_between($page, '<span class="dark_text">Type:</span>', '</a>'))) === 'music';
    $set = explode('</a>', str_get_between($page, '<span class="dark_text">Genres:</span>', '</div>'));
    foreach ($set as $item) {
      if (trim($item) !== '') {
        $clean = trim(str_get_between($item, '>'));
        if (strtolower($clean) === 'hentai') $flags->hentai = true;
      }
    }

    return $flags;
  }
}
