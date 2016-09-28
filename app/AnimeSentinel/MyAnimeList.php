<?php

namespace App\AnimeSentinel;

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
    curl_setopt($curl, CURLOPT_URL, 'https://myanimelist.net/api/anime/search.xml?q='.str_replace(' ', '+', $query));
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
   * Does an API search on MAL with the requested query.
   * Converts the results to an array of stdClass.
   *
   * @return array
   */
  public static function searchApi($query) {
    $xml = Self::searchApiXml($query);
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
    $page = Downloaders::downloadPage('https://myanimelist.net/anime.php?q='.str_replace(' ', '+', $query).'&type=0&score=0&status=0&p=0&r=0&sm=0&sd=0&sy=0&em=0&ed=0&ey=0&c[]=a&c[]=b&c[]=d&c[]=e&gx=1&genre[]=12');
    $shows = Helpers::scrape_page(str_get_between($page, '</div>Search Results</div>', '</table>'), '</tr>', [
      'mal_id' => [true, 'https://myanimelist.net/anime/', '/'],
      'thumbnail_id' => [false, '/images/anime/', '?'],
      'title' => [false, '<strong>', '</strong>'],
      'description' => [false, '<div class="pt4">', '</div>'],
      'type' => [false, 'width="45">', '</td>'],
      'episode_amount' => [false, 'width="40">', '</td>'],
      'airing' => [false, 'width="80">', ''],
    ]);

    $results = [];
    foreach ($shows as $show) {
      if (trim($show['type']) !== 'Music') {
        $result = new \stdClass();
        $result->mal = true;

        $result->mal_id = $show['mal_id'];
        $result->details_url = 'https://myanimelist.net/anime/'.$show['mal_id'];
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

        if (!empty($show['thumbnail_id'])) {
          $result->thumbnail_url = 'https://myanimelist.cdn-dena.com/images/anime/'.$show['thumbnail_id'];
        } else {
          $result->thumbnail_url = 'TODO';
        }

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
      if (trim($show['type']) !== 'Music') {
        // Get MAL data
        $data = Self::getAnimeData($show['mal_id']);
        // Check for a title match
        foreach ($data['alts'] as $alt) {
          if (match_fuzzy($alt, $title)) {
            return $show['mal_id'];
          }
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

    $title = trim(str_get_between($page, '<span itemprop="name">', '</span>'));

    $alts[] = $title;
    $set = explode('</div>', str_get_between($page, '<h2>Alternative Titles</h2>', '<br />'));
    foreach ($set as $line) {
      if (trim($line) !== '' && !str_contains($line, '<span class="dark_text">Japanese:</span>')) {
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
        $airing_start = Self::convertDetailsAiringToCarbon($aired[0]);
      }
      if ($aired[count($aired) - 1] !== '?') {
        $airing_end = Self::convertDetailsAiringToCarbon($aired[count($aired) - 1]);
      }
    }

    $thumbnail_id = str_replace('/', '-', str_get_between($page, 'src="https://myanimelist.cdn-dena.com/images/anime/', '"'));
    if (empty($thumbnail_id)) {
      $thumbnail_id = null;
    }

    $description = trim(str_get_between($page, '<span itemprop="description">', '<h2 style="margin-top: 15px;">'));
    if (str_ends_with($description, '</span>')) {
      $description = trim(str_replace_last('</span>', '', $description));
    }

    $type = strtolower(trim(str_get_between(str_get_between($page, '<span class="dark_text">Type:</span>', '</a>'), '>')));

    return [
      'mal_id' => $mal_id,
      'thumbnail_id' => $thumbnail_id,
      'title' => $title,
      'alts' => Helpers::mergeFlagAlts($alts, $mal_id),
      'description' => $description,
      'type' => !empty($type) ? $type : null,
      'genres' => $genres,
      'episode_amount' => $amount,
      'episode_duration' => $duration,
      'airing_start' => $airing_start,
      'airing_end' => $airing_end,
    ];
  }

  private static function convertDetailsAiringToCarbon($string) {
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

  private static function convertSearchAiringToCarbon($string) {
    $bits = explode('-', $string);
    if (count($bits) === 3) {
      foreach ($bits as $index => $bit) {
        if (str_contains($bit, '?')) {
          $bits[$index] = 1;
        }
      }
      $carbon = new Carbon();
      $year = Carbon::createFromFormat('y', $bits[2]);
      $carbon->year($year->year)->month($bits[0])->day($bits[1]);
      return $carbon;
    }
    return null;
  }
}
