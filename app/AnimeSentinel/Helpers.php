<?php

namespace App\AnimeSentinel;

class Helpers
{
  public static function match_titles($title1, $title2) {
    $remove = ['-', '_', '★'];

    $title1 = preg_replace('/\s/', '', strtolower($title1));
    $title2 = preg_replace('/\s/', '', strtolower($title2));;

    foreach ($remove as $char) {
      $title1 = str_replace($char, '', $title1);
      $title2 = str_replace($char, '', $title2);
    }

    return $title1 === $title2;
  }

  public static function scrape_page($page, $delim, array $request, $results = []) {
    // Explode the page
    $page_list = explode($delim, $page);

    // Turn it into an array
    foreach ($page_list as $line) {
      $index = 0;
      foreach ($request as $key => $value) {

        if (empty($value[2])) {
          // Find content between the requested strings
          if (strpos($line, $value[0]) !== false && (empty($value[1]) || strpos($line, $value[1]) !== false)) {
            if ($index === 0) {
              $results[] = [
                $key => str_get_between($line, $value[0], $value[1])
              ];
            } else {
              $results[count($results) - 1][$key] = str_get_between($line, $value[0], $value[1]);
            }
          }
        }

        else {
          // Set content depending on string presence
          if (strpos($line, $value[2]) !== false) {
            $data = $value[1];
          } else {
            $data = $value[0];
          }
          if ($index === 0) {
            $results[] = [
              $key => $data
            ];
          } else {
            $results[count($results) - 1][$key] = $data;
          }
        }

        $index++;
      }
    }

    // Return results
    return $results;
  }
}
