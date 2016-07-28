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
