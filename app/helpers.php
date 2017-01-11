<?php

function mailAnomaly($show, $description, $vars = []) {
  $tos = config('mail.debug_addresses');
  if (str_replace('.', '', mb_strtolower($description)) === 'could not find show on mal') {
    $tos = array_merge($tos, config('mail.admin_addresses'));
  }
  try {
    \Mail::send('emails.reports.show', ['show' => $show, 'description' => $description, 'vars' => $vars], function ($m) use ($tos) {
      $m->subject('AnimeSentinel Anomaly Report');
      $m->from('reports.animesentinel@wilcodeboer.me', 'AnimeSentinel Reports');
      $m->to($tos);
    });
  } catch (\Exception $e) {}
}

function mailException($description, $exception, $vars = []) {
  $vars[] = $exception;
  try {
    \Mail::send('emails.reports.general', ['description' => $description, 'vars' => $vars], function ($m) {
      $m->subject('AnimeSentinel Exception Report');
      $m->from('reports.animesentinel@wilcodeboer.me', 'AnimeSentinel Reports');
      $m->to(config('mail.debug_addresses'));
    });
  } catch (\Exception $e) {}
}

function flash($content, $level) {
  $message = new stdClass();
  $message->body = $content;
  $message->type = 'alert-'.$level;

  $messages = Session::pull('alerts_'.$level);
  $messages[] = $message;
  Session::flash('alerts_'.$level, $messages);
}

function flash_info($content) {
  flash($content, 'info');
}

function flash_success($content) {
  flash($content, 'success');
}

function flash_warning($content) {
  flash($content, 'warning');
}

function flash_error($content) {
  flash($content, 'danger');
}

function visitPage($id) {
  if (!session()->has('visited_'.$id) || !session()->get('visited_'.$id)) {
    session()->put('visited_'.$id, true);
    return false;
  }
  return true;
}

function badNotes($notes) {
  $notes = strtolower($notes);
  if (str_contains($notes, 'lq') || str_contains($notes, 'broken')) {
    return true;
  }
  return false;
}

function fancyDuration($seconds, $showSeconds = true) {
  $hours = floor($seconds / 3600);
  $minutes = floor(($seconds - ($hours * 3600)) / 60);
  $seconds = round($seconds - ($minutes * 60) - ($hours * 3600));
  if ($hours > 0) {
    return $hours.' h. '.$minutes.' min.';
  } elseif($showSeconds) {
    return $minutes.' min. '.$seconds.' sec.';
  } else {
    return $minutes.' min.';
  }
}

/**
 * Assuming a nested array containing unique values,
 * this will return all values 'lower' than the start value.
 *
 * @return array
 */
function array_get_childs(array $array, $start, $childs = [], $adding = false) {
  foreach ($array as $value) {
    if (is_array($value)) {
      $childs = array_get_childs($value, $start, $childs, $adding);
    }
    elseif ($value === $start) {
      $adding = true;
    }
    elseif ($adding) {
      $childs[] = $value;
    }
  }
  return $childs;
}

/**
 * Assuming a nested array containing unique values,
 * this will return all values 'higher' than the start value.
 *
 * @return array
 */
function array_get_parents(array $array, $start, $parents = [], $gathered = []) {
  foreach ($array as $value) {
    if (is_array($value)) {
      $parents = array_get_parents($value, $start, $parents, $gathered);
    }
    elseif ($value === $start) {
      $parents = $gathered;
    }
    else {
      $gathered[] = $value;
    }
  }
  return $parents;
}

function fullUrl($path, $production = false) {
  $appUrl = $production ? config('app.url_production') : config('app.url');
  if (str_starts_with($path, '/')) {
    $path = str_replace_first('/', '', $path);
  }
  if (str_ends_with($appUrl, '/')) {
    $appUrl = str_replace_last('/', '', $appUrl);
  }
  if (!empty($path)) {
    return $appUrl.'/'.$path;
  } else {
    return $appUrl;
  }
}

function slugify($string) {
  $string = mb_strtolower($string);

  $replace = [
    '/' => '⧸',
    '\\' => '⧹',
    '-' => '‑',
    ' ' => '-',
  ];
  foreach ($replace as $from => $to) {
    $string = str_replace($from, $to, $string);
  }

  return $string;
}

function deslugify($string) {
  $string = mb_strtolower($string);

  $replace = [
    '⧸' => '/',
    '⧹' => '\\',
    '-' => ' ',
    '‑' => '-',
  ];
  foreach ($replace as $from => $to) {
    $string = str_replace($from, $to, $string);
  }

  return $string;
}

// String Helpers //

function str_get_between($string, $start, $end = '', $last = false) {
  // TODO: improve this function

  if (empty($start)) {
    $ini = 0;
  }
  else {
    if ($last) {
      $ini = strrpos($string, $start);
    } else {
      $ini = strpos($string, $start);
    }
  }

  if ($ini === false) return false;
  $ini += strlen($start);

  if (empty($end)) {
    return substr($string, $ini);
  } else {
    $len = strpos($string, $end, $ini);
    if ($len === false) return false;
    $len -= $ini;
    return substr($string, $ini, $len);
  }
}

function match_fuzzy($title1, $title2) {
  return str_fuzz($title1) === str_fuzz($title2);
}

function str_starts_with($haystack, $needle) {
  // search backwards starting from haystack length characters from the end
  return $needle === '' || strrpos($haystack, $needle, - strlen($haystack)) !== false;
}

function str_ends_with($haystack, $needle) {
  // search forward starting from end minus needle length characters
  return $needle === '' || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== false);
}

function str_fuzz($string, $removeColon = true) {
  $replace = [
    ' and ' => '&',
    ' to ' => '&',
    ' und ' => '&',
    ' & ' => '&',
  ];

  $string = mb_strtolower(trim($string));
  foreach ($replace as $from => $to) {
    $string = str_replace($from, $to, $string);
  }
  if ($removeColon) {
    $string = str_replace(': ', ' ', $string);
  }

  return $string;
}

function str_to_url($string, $delim = '-', $preg = '/[^a-zA-Z0-9α-ωΑ-Ω\\-\\_]/u') {
  $string = mb_strtolower(trim($string));
  $replace = [
    '\'' => '',
    '`' => '',
  ];
  foreach ($replace as $from => $to) {
    $string = str_replace($from, $to, $string);
  }
  $string = preg_replace($preg, $delim, $string);
  $string = preg_replace('/'.$delim.'+/', $delim, $string);
  $string = preg_replace('/^'.$delim.'+/', '', $string);
  $string = preg_replace('/'.$delim.'+$/', '', $string);
  return $string;
}

function str_from_url($url, $delims = ['-']) {
  foreach ($delims as $delim) {
    $url = str_replace($delim, ' ', $url);
  }
  return ucwords($url);
}

function str_slugify($string) {
  $slugify = new \Cocur\Slugify\Slugify();
  return $slugify->slugify($string);
}

function htmlentities_decode($string) {
  return html_entity_decode(preg_replace_callback("/(&#[0-9]+;)/", function($m) { return mb_convert_encoding($m[1], "UTF-8", "HTML-ENTITIES"); }, $string));
}
