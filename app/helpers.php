<?php

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
  flash($content, 'error');
}

function visitPage($id) {
  if (!session()->has('visited_'.$id) || !session()->get('visited_'.$id)) {
    session()->put('visited_'.$id, true);
    return false;
  }
  return true;
}

function playerSupport($filename) {
  if (str_ends_with($filename, '.mp4') || strpos($filename, 'redirector.googlevideo.com') !== false || strpos($filename, '2.bp.blogspot.com') !== false) {
    return true;
  }
  return false;
}

function badNotes($notes) {
  $notes = strtolower($notes);
  if (strpos($notes, 'lq') !== false || strpos($notes, 'broken') !== false) {
    return true;
  }
  return false;
}

// String Helpers //

function str_get_between($string, $start, $end = '', $last = false) {
  // TODO: improve this function

  if ($last) {
    $ini = strrpos($string, $start);
  } else {
    $ini = strpos($string, $start);
  }

  if ($ini === false) return false;
  $ini += strlen($start);

  if (empty($end)) {
    return substr($string, $ini);
  } else {
    $len = strpos($string, $end, $ini) - $ini;
    return substr($string, $ini, $len);
  }
}

function str_starts_with($haystack, $needle) {
    // search backwards starting from haystack length characters from the end
    return $needle === "" || strrpos($haystack, $needle, - strlen($haystack)) !== false;
}

function str_ends_with($haystack, $needle) {
    // search forward starting from end minus needle length characters
    return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== false);
}

function str_urlify($string) {
  $string = trim($string); // TODO: properly convert string to lowercase
  $string = preg_replace('/[^a-zA-Z0-9α-ωΑ-Ω\-_]/u', '-', $string);
  $string = preg_replace('/-+/', '-', $string);
  $string = preg_replace('/^-+/', '', $string);
  $string = preg_replace('/-+$/', '', $string);
  return $string;
}
