<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

use App\AnimeSentinel\ShowManager;
use App\Show;

class ShowUpdate implements ShouldQueue
{
  use InteractsWithQueue, Queueable, SerializesModels;
  public $db_data;

  protected $show;
  protected $episodes;
  protected $queue;

  /**
   * Create a new job instance.
   *
   * @return void
   */
  public function __construct($show_id, $episodes = false, $queue = 'default') {
    // Get the show
    $show = Show::find($show_id);

    $this->show = $show;
    $this->episodes = $episodes;
    $this->queue = $queue;
    // Set special database data
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
    ShowManager::updateShowCache($this->show, $this->episodes, $this->queue, $this);
  }
}
