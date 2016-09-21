<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

use App\Video;
use App\AnimeSentinel\Actions\VideoManager;

class VideoRefreshLink implements ShouldQueue
{
  use InteractsWithQueue, Queueable, SerializesModels;
  public $db_data;

  protected $video_id;

  /**
   * Create a new job instance.
   *
   * @return void
   */
  public function __construct(Video $video) {
    $this->video_id = $video->id;
    // Set special database data
    $this->db_data = [
      'job_task' => 'VideoRefreshLink',
      'show_id' => $video->show->mal_id !== null ? $video->show->mal_id : $video->show->title,
      'job_data' => ['video_id' => $video->id],
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
      VideoManager::refreshVideoLinkFor($video, true);
    }
  }
}
