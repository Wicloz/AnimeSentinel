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
  public function __construct($mal_id = null, $title = null) {
    $this->mal_id = $mal_id;
    $this->title = $title;
    // Set special database data
    $this->db_data = [
      'job_task' => 'ShowAdd',
      'show_id' => $title !== null ? $title : $mal_id,
      'job_data' => null,
    ];
  }

  /**
   * Execute the job.
   *
   * @return void
   */
  public function handle() {
    if (isset($this->$title)) {
      ShowManager::addShowWithTitle($this->title, 'default', true);
    } else {
      ShowManager::addShowWithMalId($this->mal_id, 'default', true);
    }
  }
}
