<?php

namespace App\Jobs;

use App\Jobs\Job;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

use App\AnimeSentinel\ConnectionManager;

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
      'show_malid' => null,
      'job_data' => null,
    ];
  }

  /**
   * Execute the job.
   *
   * @return void
   */
  public function handle() {
    ConnectionManager::findRecentEpisodes();
    queueJob((new FindRecentVideos)->delay(300), 'periodic_low');
  }

  /**
   * Handle a job failure.
   *
   * @return void
   */
  public function failed() {
    queueJob((new FindRecentVideos)->delay(300), 'periodic_low');
  }
}
