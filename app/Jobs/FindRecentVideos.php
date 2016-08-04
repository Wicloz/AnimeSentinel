<?php

namespace App\Jobs;

use App\Jobs\Job;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

use \App\AnimeSentinel\StreamingManager;

class FindRecentVideos extends Job implements ShouldQueue
{
  use InteractsWithQueue;

  /**
   * Execute the job.
   *
   * @return void
   */
  public function handle() {
    StreamingManager::findRecentEpisodes();
  }
}
