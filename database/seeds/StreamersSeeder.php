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
    $s2->enabled = false;
    $s2->save();

    $s3 = Streamer::findOrNew('nineanime');
    $s3->id = 'nineanime';
    $s3->name = '9ANIME';
    $s3->link_home = 'https://9anime.to';
    $s3->enabled = false;
    $s3->save();
  }
}
