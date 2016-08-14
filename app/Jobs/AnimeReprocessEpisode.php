<?php

namespace App\Jobs;

use App\Jobs\Job;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

use App\Show;
use App\AnimeSentinel\VideoManager;

class AnimeReprocessEpisode extends Job implements ShouldQueue
{
  use InteractsWithQueue;

  /**
   * Create a new job instance.
   *
   * @return void
   */
  public function __construct(Show $show, $translation_types, $episode_num, $streamer_id = null) {
    $this->show_id = $show->id;
    $this->translation_types = $translation_types;
    $this->episode_num = $episode_num;
    $this->streamer_id = $streamer_id;
    // Set special database data
    $this->db_data = [
      'job_task' => 'AnimeReprocessEpisode',
      'show_title' => $show->title,
      'job_data' => [
        'translation_types' => $translation_types,
        'episode_num' => $episode_num,
        'streamer_id' => $streamer_id,
      ],
    ];
  }

  /**
   * Execute the job.
   *
   * @return void
   */
  public function handle() {
    VideoManager::reprocessEpsiode(Show::find($this->show_id), $this->translation_types, $this->episode_num, $this->streamer_id);
  }
}
