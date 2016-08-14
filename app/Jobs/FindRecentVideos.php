<?php

namespace App\Jobs;

use App\Jobs\Job;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

use App\AnimeSentinel\StreamingManager;

class FindRecentVideos extends Job implements ShouldQueue
{
  use InteractsWithQueue;

  /**
   * Create a new job instance.
   *
   * @return void
   */
  public function __construct() {
    // Set special database data
    $this->db_data = [
      'job_task' => 'FindRecentVideos',
      'show_title' => null,
      'job_data' => null,
    ];
  }

  /**
   * Execute the job.
   *
   * @return void
   */
  public function handle() {
    StreamingManager::findRecentEpisodes();
    queueJob((new FindRecentVideos)->delay(300), 'periodic_low');
  }
}
