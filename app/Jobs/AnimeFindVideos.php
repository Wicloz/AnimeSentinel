<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

use App\Show;
use App\AnimeSentinel\Actions\FindVideos;

class AnimeFindVideos implements ShouldQueue
{
  use InteractsWithQueue, Queueable, SerializesModels;
  public $db_data;

  protected $show;

  /**
   * Create a new job instance.
   *
   * @return void
   */
  public function __construct(Show $show) {
    $this->show = $show;
    // Set special database data
    $this->db_data = [
      'job_task' => 'AnimeFindVideos',
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
    FindVideos::findVideosForShow($this->show, true);
  }
}
