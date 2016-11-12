<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

use App\AnimeSentinel\Actions\ShowManager;

class ShowAdd implements ShouldQueue
{
  use InteractsWithQueue, Queueable, SerializesModels;
  public $db_data;

  protected $mal_id;
  protected $title;
  protected $allowNonMal;
  protected $targetQueue;

  /**
   * Create a new job instance.
   *
   * @return void
   */
  public function __construct($mal_id = null, $title = null, $allowNonMal = false, $targetQueue = 'default') {
    $this->mal_id = $mal_id;
    $this->title = $title;
    $this->allowNonMal = $allowNonMal;
    $this->targetQueue = $targetQueue;
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
    if (isset($this->title)) {
      ShowManager::addShowWithTitle($this->title, $this->allowNonMal, $this->targetQueue, true);
    } elseif (isset($this->mal_id)) {
      ShowManager::addShowWithMalId($this->mal_id, $this->targetQueue, true);
    }
  }
}
