<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

use App\Show;
use App\AnimeSentinel\Actions\FindVideos;

class AnimeReprocessEpisodes implements ShouldQueue
{
  use InteractsWithQueue, Queueable, SerializesModels;
  public $db_data;

  protected $show;
  protected $translation_types;
  protected $episode_num;
  protected $streamer_id;

  /**
   * Create a new job instance.
   *
   * @return void
   */
  public function __construct(Show $show, $translation_types = ['sub', 'dub'], $episode_num = null, $streamer_id = null) {
    $this->show = $show;
    $this->translation_types = $translation_types;
    $this->episode_num = $episode_num;
    $this->streamer_id = $streamer_id;
    // Set special database data
    $this->db_data = [
      'job_task' => 'AnimeReprocessEpisodes',
      'show_id' => $show->mal_id !== null ? $show->mal_id : $show->title,
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
    FindVideos::reprocessEpsiodes($this->show, $this->translation_types, $this->episode_num, $this->streamer_id, true);
  }
}
