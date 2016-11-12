<?php

namespace App\AnimeSentinel;

use Carbon\Carbon;
use App\ShowFlag;

class Helpers
{
  public static function mergeFlagAlts($alts, $mal_id) {
    $showFlag = ShowFlag::find($mal_id);

    if (!empty($showFlag)) {
      foreach ($showFlag->alt_rules as $data => $action) {
        switch ($action) {
          case '+':
            $alts[] = $data;
          break;
          case '-':
            array_splice($alts, array_search($data, $alts), 1);
          break;
        }
      }
    }

    return $alts;
  }

  public static function scrape_page($page, $delim, array $request, $results = []) {
    // Explode the page
    $page_list = explode($delim, $page);

    // Turn it into an array
    foreach ($page_list as $line) {
      foreach ($request as $key => $value) {

        $var = str_get_between($value[1], '{{', '}}');
        if (!empty($var) && count($results) >= 1) {
          $value[1] = str_replace('{{'.$var.'}}', $results[count($results) - 1][$var], $value[1]);
        }
        $var = str_get_between($value[2], '{{', '}}');
        if (!empty($var) && count($results) >= 1) {
          $value[2] = str_replace('{{'.$var.'}}', $results[count($results) - 1][$var], $value[2]);
        }

        if (empty($value[3])) {
          // Find content between the requested strings
          if (str_contains($line, $value[1]) && (empty($value[2]) || str_contains($line, $value[2]))) {
            if ($value[0]) {
              $results[] = [
                $key => str_get_between($line, $value[1], $value[2])
              ];
            } else {
              $results[count($results) - 1][$key] = str_get_between($line, $value[1], $value[2]);
            }
          }
        }

        else {
          // Set content depending on string presence
          if (str_contains($line, $value[3])) {
            $data = $value[2];
          } else {
            $data = $value[1];
          }
          if ($value[0] && $data === $value[2]) {
            $results[] = [
              $key => $data
            ];
          } elseif (!$value[0] && (!isset($results[count($results) - 1][$key]) || $results[count($results) - 1][$key] !== $value[2])) {
            $results[count($results) - 1][$key] = $data;
          }
        }

      }
    }

    // Return results
    return $results;
  }
}
