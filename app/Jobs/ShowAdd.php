<?php

namespace App\Jobs;

use App\Jobs\Job;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

use \App\AnimeSentinel\ShowManager;

class ShowAdd extends Job implements ShouldQueue
{
  use InteractsWithQueue;

  /**
   * Create a new job instance.
   *
   * @return void
   */
  public function __construct($title) {
    $this->title = $title;
  }

  /**
   * Execute the job.
   *
   * @return void
   */
  public function handle() {
    ShowManager::addShowWithTitle($this->title);
  }
}
