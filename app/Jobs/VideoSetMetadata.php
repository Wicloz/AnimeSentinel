<?php

namespace App\Jobs;

use App\Jobs\Job;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

use App\Video;
use App\AnimeSentinel\VideoManager;

class VideoSetMetadata extends Job implements ShouldQueue
{
  use InteractsWithQueue;

  /**
   * Create a new job instance.
   *
   * @return void
   */
  public function __construct(Video $video) {
    $this->video_id = $video->id;
    // Set special database data
    $this->db_data = [
      'job_task' => 'VideoSetMetadata',
      'show_id' => $video->show->mal_id !== null ? $video->show->mal_id : $video->show->title,
      'job_data' => $video->id,
    ];
  }

  /**
   * Execute the job.
   *
   * @return void
   */
  public function handle() {
    $video = Video::find($this->video_id);
    if (isset($video)) {
      VideoManager::setMetaDataFor($video, true);
    }
  }
}
