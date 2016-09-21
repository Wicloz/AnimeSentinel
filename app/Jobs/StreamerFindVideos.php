<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

use App\Streamer;
use App\AnimeSentinel\Actions\FindVideos;

class StreamerFindVideos implements ShouldQueue
{
  use InteractsWithQueue, Queueable, SerializesModels;
  public $db_data;

  protected $streamer;

  /**
   * Create a new job instance.
   *
   * @return void
   */
  public function __construct(Streamer $streamer) {
    $this->streamer = $streamer;
    // Set special database data
    $this->db_data = [
      'job_task' => 'StreamerFindVideos',
      'show_id' => null,
      'job_data' => ['streamer_id' => $streamer->id],
    ];
  }

  /**
   * Execute the job.
   *
   * @return void
   */
  public function handle() {
    FindVideos::findVideosForStreamer($this->streamer, true);
  }
}
