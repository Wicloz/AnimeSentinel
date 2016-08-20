<?php

function mailAnomaly($show, $description, $vars = []) {
  \Mail::send('emails.report_show', ['show' => $show, 'description' => $description, 'vars' => $vars], function ($m) {
    $m->subject('AnimeSentinel Anomaly Report');
    $m->from('reports.animesentinel@wilcodeboer.me', 'AnimeSentinel Reports');
    $m->to('animesentinel@wilcodeboer.me');
  });
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

function queueJob($job, $queue = 'default') {
  // Prepare job data
  $job_data = $job->db_data;
  unset($job->db_data);

  if ($job_data['show_id'] !== null) {
    // Check whether a higher job is queued
    $highers = \App\Job::higherThan($job_data['job_task'], $job_data['show_id']);
    if (count($highers) > 0) {
      foreach ($highers as $higher) {
        $higher->elevateQueue($queue);
      }
      return;
    }

    // Check whether the same job is already queued
    $duplicates = \App\Job::where([
      ['job_task', '=', $job_data['job_task']],
      ['show_id', '=', $job_data['show_id']],
      ['job_data', '=', json_encode($job_data['job_data'])],
      ['reserved', '=', 0],
    ])->get();
    if (count($duplicates) > 0) {
      foreach ($duplicates as $duplicate) {
        $duplicate->elevateQueue($queue);
      }
      return;
    }

    // Remove any lower jobs
    $newQueue = \App\Job::deleteLowerThan($job_data['job_task'], $job_data['show_id']);
    if (in_array($newQueue, array_get_parents(config('queue.queuehierarchy'), $queue))) {
      $queue = $newQueue;
    }
  }

  // Add this job to the queue
  $job_id = dispatch($job->onQueue($queue));
  $job = \App\Job::find($job_id);
  $job->job_task = $job_data['job_task'];
  $job->show_id = $job_data['show_id'];
  $job->job_data = $job_data['job_data'];
  $job->save();
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
    $pos1 = strpos($path, '/');
    $path = substr_replace($path, '', $pos1, strlen('/'));
  }
  if (str_ends_with($appUrl, '/')) {
    $pos2 = strlen($appUrl) - 1;
    $appUrl = substr_replace($appUrl, '', $pos2, strlen('/'));
  }
  return $appUrl.'/'.$path;
}

function slugify($text) {
  return str_replace(' ', '‑', mb_strtolower($text));
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

function match_fuzzy($title1, $title2) {
  return str_fuzz($title1) === str_fuzz($title2);
}

function str_starts_with($haystack, $needle) {
  // search backwards starting from haystack length characters from the end
  return $needle === "" || strrpos($haystack, $needle, - strlen($haystack)) !== false;
}

function str_ends_with($haystack, $needle) {
  // search forward starting from end minus needle length characters
  return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== false);
}

function str_fuzz($string) {
  return str_replace(' & ', '&', str_replace(' to ', '&', str_replace(' and ', '&', mb_strtolower(trim($string)))));
}

function str_urlify($string) {
  $string = mb_strtolower(trim($string));
  $string = preg_replace('/[`\']/u', '', $string);
  $string = preg_replace('/[^a-zA-Z0-9α-ωΑ-Ω\-_]/u', '-', $string);
  $string = preg_replace('/-+/', '-', $string);
  $string = preg_replace('/^-+/', '', $string);
  $string = preg_replace('/-+$/', '', $string);
  return $string;
}

function htmlentities_decode($string) {
  return html_entity_decode(preg_replace_callback("/(&#[0-9]+;)/", function($m) { return mb_convert_encoding($m[1], "UTF-8", "HTML-ENTITIES"); }, $string));
}
