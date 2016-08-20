<?php

namespace App\AnimeSentinel;

use App\Streamer;
use App\Video;
use Carbon\Carbon;

class VideoManager
{
  /**
   * Saves the passed in video objects after:
   * 1. Attempting te set more accurate video data using ffprobe
   */
  public static function saveVideos($videos) {
    foreach ($videos as $video) {
      if (playerSupport($video->link_video)) {
        $data = json_decode(shell_exec('ffprobe -v quiet -print_format json -show_streams "'. $video->link_video .'"'));
        foreach ($data->streams as $stream) {
          if ($stream->codec_type === 'video') {
            $video->encoding = 'video/'.$stream->codec_name;
            $video->resolution = $stream->width.'x'.$stream->height;
            $video->duration = $stream->duration;
            //$video->uploadtime = Carbon::createFromFormat('Y-m-d H:i:s', $stream->tags->creation_time);
          }
        }
      }

      $video->save();
    }
  }

  /**
   * Finds the video link for the requested video from it's streaming site.
   *
   * @return string
   */
  public static function findVideoLink($video) {
    $class = '\\App\\AnimeSentinel\\Connectors\\'.$video->streamer_id;
    return $class::findVideoLink($video);
  }
}
