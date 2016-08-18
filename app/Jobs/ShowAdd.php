<?php

namespace App\Jobs;

use App\Jobs\Job;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

use App\AnimeSentinel\ShowManager;

class ShowAdd extends Job implements ShouldQueue
{
  use InteractsWithQueue;

  /**
   * Create a new job instance.
   *
   * @return void
   */
  public function __construct($title, $mal_id = null) {
    $this->title = $title;
    $this->mal_id = $mal_id;
    // Set special database data
    $this->db_data = [
      'job_task' => 'ShowAdd',
      'show_title' => $title,
      'job_data' => null,
    ];
  }

  /**
   * Execute the job.
   *
   * @return void
   */
  public function handle() {
    if (isset($this->mal_id)) {
      ShowManager::addShowWithMalId($this->mal_id, $this->title, 'default', true);
    } else {
      ShowManager::addShowWithTitle($this->title, 'default', true);
    }
  }
}
