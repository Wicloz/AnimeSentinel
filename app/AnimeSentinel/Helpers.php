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

  public static function str_get_between($string, $start, $end = '') {
    // TODO: improve this function

    $string = ' ' . $string;
    $ini = strpos($string, $start);
    if ($ini == 0) return '';
    $ini += strlen($start);

    if ($end === '') {
      return substr($string, $ini);
    } else {
      $len = strpos($string, $end, $ini) - $ini;
      return substr($string, $ini, $len);
    }
  }
}
