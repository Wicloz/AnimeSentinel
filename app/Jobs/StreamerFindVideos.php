<?php

namespace App\Jobs;

use App\Jobs\Job;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

use App\Streamer;
use App\AnimeSentinel\StreamingManager;

class StreamerFindVideos extends Job implements ShouldQueue
{
  use InteractsWithQueue;

  /**
   * Create a new job instance.
   *
   * @return void
   */
  public function __construct(Streamer $streamer) {
    $this->streamer_id = $streamer->id;
    // Set special database data
    $this->db_data = [
      'job_task' => 'StreamerFindVideos',
      'show_title' => null,
      'job_data' => $streamer->id,
    ];
  }

  /**
   * Execute the job.
   *
   * @return void
   */
  public function handle() {
    StreamingManager::findVideosForStreamer(Streamer::find($this->streamer_id));
  }
}
