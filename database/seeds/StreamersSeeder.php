<?php

use Illuminate\Database\Seeder;
use App\Streamer;

class StreamersSeeder extends Seeder
{
  /**
   * Streaming sites are defined here.
   *
   * @return void
   */
  public function run() {
    $s1 = Streamer::findOrNew('animeshow');
    $s1->id = 'animeshow';
    $s1->name = 'AnimeShow.tv';
    $s1->link_home = 'http://animeshow.tv';
    $s1->save();
  }
}
