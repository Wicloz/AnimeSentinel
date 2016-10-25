<?php

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

use App\AnimeSentinel\Downloaders;

class DownloadTest extends TestCase
{
  public function testCloudflare() {
    $this->assertContains('KissAnime - Watch anime online in high quality', Downloaders::downloadPage('http://kissanime.to/'));
  }

  // public function testJavascript() {
  //   $this->assertContains('Your Anime community entertainment center.', Downloaders::downloadPage('https://htvanime.com/'));
  // }

  public function testScrolled() {
    $this->assertContains('Zoku Natsume Yuujinchou: 3D Nyanko-sensei Gekijou', Downloaders::downloadPage('https://myanimelist.net/animelist/Wicloz'));
  }
}
