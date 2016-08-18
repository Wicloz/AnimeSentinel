<?php

namespace App\Jobs;

use App\Jobs\Job;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

use App\AnimeSentinel\ShowManager;
use App\Show;

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
    $mode = $episodes ? 'true' : 'false';
    $this->db_data = [
      'job_task' => 'ShowUpdate('.$mode.')',
      'show_id' => Show::find($show_id)->mal_id,
      'job_data' => null,
    ];
  }

  /**
   * Execute the job.
   *
   * @return void
   */
  public function handle() {
    ShowManager::updateShowCache($this->show_id, $this->episodes, 'default', true);
  }
}
