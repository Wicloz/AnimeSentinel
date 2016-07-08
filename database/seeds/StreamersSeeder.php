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

    $s2 = Streamer::findOrNew('daisuki');
    $s2->id = 'daisuki';
    $s2->name = 'DAISUKI';
    $s2->link_home = 'http://www.daisuki.net';
    $s2->save();
  }
}
