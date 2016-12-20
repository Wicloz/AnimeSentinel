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
    $s1->enabled = true;
    $s1->save();

    $s2 = Streamer::findOrNew('kissanime');
    $s2->id = 'kissanime';
    $s2->name = 'KissAnime';
    $s2->link_home = 'http://kissanime.ru';
    $s2->enabled = true;
    $s2->save();
  }
}
