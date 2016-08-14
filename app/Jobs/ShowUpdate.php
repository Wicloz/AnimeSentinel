<?php

namespace App\Jobs;

use App\Jobs\Job;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

use \App\AnimeSentinel\ShowManager;

class ShowUpdate extends Job implements ShouldQueue
{
  use InteractsWithQueue;

  /**
   * Create a new job instance.
   *
   * @return void
   */
  public function __construct($show_id, $episodes = false) {
    $this->show_id = $show_id;
    $this->episodes = $episodes;
    // Set special database data
    $this->db_data = [
      'job_task' => 'ShowUpdate',
      'show_title' => Show::find($show_id)->title,
      'job_data' => $episodes,
    ];
  }

  /**
   * Execute the job.
   *
   * @return void
   */
  public function handle() {
    ShowManager::updateShowCache($this->show_id, $this->episodes);
  }
}
