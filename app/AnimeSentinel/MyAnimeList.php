<?php

namespace App\AnimeSentinel;

use App\MalFlag;

class MyAnimeList
{
  /**
   * Does a search on MAL with the requested query.
   * Returns all results.
   *
   * @return SimpleXMLElement
   */
  private static function searchApi($query) {
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
  public static function search($query) {
    $xml = Self::searchApi($query);
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
   * Does a search on MAL with the requested query.
   * Returns only the first result where the query matches one of the titles
   *
   * @return stdClass
   */
  public static function searchStrict($query) {
    $results = Self::search($query);

    foreach ($results as $result) {
      // Create alts list
      $alts[] = $result->title;
      $alts[] = $result->english;
      $alts = array_merge($alts, $result->synonyms);
      // Check for a match
      foreach ($alts as $alt) {
        if (Helpers::match_titles($alt, $query)) {
          return $result;
        }
      }
    }

    return false;
  }

  /**
   * Scrapes all important data from MAL for the anime with the requested id.
   *
   * @return array
   */
  public static function getAnimeData($mal_id) {
    $page = file_get_contents('http://myanimelist.net/anime/'.$mal_id);

    $title = trim(Helpers::str_get_between($page, '<span itemprop="name">', '</span>'));

    $alts[] = $title;
    $set = explode('</div>', Helpers::str_get_between($page, '<h2>Alternative Titles</h2>', '<br />'));
    foreach ($set as $line) {
      if (trim($line) !== '') {
        $list = trim(Helpers::str_get_between($line, '</span>'));
        $alts = array_merge($alts, explode(', ', $list));
      }
    }

    $amount = Helpers::str_get_between($page, '<span class="dark_text">Episodes:</span>', '</div>');
    settype($amount, 'int');
    if ($amount === 0) $amount = -1;

    $durations = explode('hr.', trim(Helpers::str_get_between($page, '<span class="dark_text">Duration:</span>', '</div>')));
    $duration = 0;
    if (count($durations) === 1) {
      $duration += $durations[0];
    } else if (count($durations) === 2) {
      $duration += $durations[0] * 60;
      $duration += $durations[1];
    }
    if ($duration === 0) $duration = -1;

    $genres = [];
    $set = explode('</a>', Helpers::str_get_between($page, '<span class="dark_text">Genres:</span>', '</div>'));
    foreach ($set as $item) {
      if (trim($item) !== '') {
        $genres[] = strtolower(trim(Helpers::str_get_between($item, '>')));
      }
    }

    return [
      'mal_id' => $mal_id,
      'thumbnail_id' => str_replace('/', '-', Helpers::str_get_between($page, 'data-src="http://cdn.myanimelist.net/images/anime/', '"')),
      'title' => $title,
      'alts' => $alts,
      'description' => trim(Helpers::str_get_between($page, '<span itemprop="description">', '</span>')),
      'show_type' => strtolower(trim(Helpers::str_get_between(Helpers::str_get_between($page, '<span class="dark_text">Type:</span>', '</a>'), '>'))),
      'genres' => $genres,
      'episode_amount' => $amount,
      'episode_duration' => $duration,
    ];
  }

  /**
   * Finds out if the requested anime is hentai or music rather than an anime.
   *
   * @return stdClass
   */
  public static function malFlags($mal_id) {
    $page = file_get_contents('http://myanimelist.net/anime/'.$mal_id);
    $flags = (object) ['hentai' => false];

    $flags->music = strtolower(trim(Helpers::str_get_between($page, '<span class="dark_text">Type:</span>', '</a>'))) === 'music';
    $set = explode('</a>', Helpers::str_get_between($page, '<span class="dark_text">Genres:</span>', '</div>'));
    foreach ($set as $item) {
      if (trim($item) !== '') {
        $clean = trim(Helpers::str_get_between($item, '>'));
        if (strtolower($clean) === 'hentai') $flags->hentai = true;
      }
    }

    return $flags;
  }
}
