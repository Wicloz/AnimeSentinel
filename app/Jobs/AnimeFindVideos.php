<?php

namespace App\Jobs;

use App\Jobs\Job;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

use App\Show;
use App\AnimeSentinel\ConnectionManager;

class AnimeFindVideos extends Job implements ShouldQueue
{
  use InteractsWithQueue;

  /**
   * Create a new job instance.
   *
   * @return void
   */
  public function __construct(Show $show) {
    $this->show_id = $show->id;
    // Set special database data
    $this->db_data = [
      'job_task' => 'AnimeFindVideos',
      'show_title' => $show->title,
      'job_data' => null,
    ];
  }

  /**
   * Execute the job.
   *
   * @return void
   */
  public function handle() {
    ConnectionManager::findVideosForShow(Show::find($this->show_id));
  }
}
