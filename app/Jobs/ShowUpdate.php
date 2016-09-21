<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

use App\AnimeSentinel\Actions\ShowManager;
use App\Show;

class ShowUpdate implements ShouldQueue
{
  use InteractsWithQueue, Queueable, SerializesModels;
  public $db_data;

  protected $show_id;
  protected $episodes;
  protected $targetQueue;

  /**
   * Create a new job instance.
   *
   * @return void
   */
  public function __construct($show_id, $episodes = false, $targetQueue = 'default') {
    $this->show_id = $show_id;
    $this->episodes = $episodes;
    $this->targetQueue = $targetQueue;
    // Set special database data
    $show = Show::find($show_id);
    $mode = $episodes ? 'true' : 'false';
    $this->db_data = [
      'job_task' => 'ShowUpdate('.$mode.')',
      'show_id' => $show->mal_id !== null ? $show->mal_id : $show->title,
      'job_data' => null,
    ];
  }

  /**
   * Execute the job.
   *
   * @return void
   */
  public function handle() {
    ShowManager::updateShowCache($this->show_id, $this->episodes, $this->targetQueue, true);
  }
}
