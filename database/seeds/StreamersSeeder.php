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

    $s2 = Streamer::findOrNew('kissanime');
    $s2->id = 'kissanime';
    $s2->name = 'KissAnime';
    $s2->link_home = 'http://kissanime.to';
    $s2->save();

    //$s3 = Streamer::findOrNew('kisscartoon');
    //$s3->id = 'kisscartoon';
    //$s3->name = 'KissCartoon';
    //$s3->link_home = 'http://kisscartoon.me';
    //$s3->save();
  }
}
