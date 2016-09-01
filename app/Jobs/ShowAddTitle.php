<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

use App\AnimeSentinel\ShowManager;

class ShowAddTitle implements ShouldQueue
{
  use InteractsWithQueue, Queueable, SerializesModels;
  public $db_data;

  protected $title;
  protected $allowNonMal;
  protected $queue;

  /**
   * Create a new job instance.
   *
   * @return void
   */
  public function __construct($title, $allowNonMal, $queue = 'default') {
    $this->title = $title;
    $this->allowNonMal = $allowNonMal;
    $this->queue = $queue;
    // Set special database data
    $this->db_data = [
      'job_task' => 'ShowAdd',
      'show_id' => $title,
      'job_data' => null,
    ];
  }

  /**
   * Execute the job.
   *
   * @return void
   */
  public function handle() {
    ShowManager::addShowWithTitle($this->title, $this->allowNonMal, $this->queue, $this);
  }
}
