<?php

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

use App\AnimeSentinel\Downloaders;

class DownloadTest extends TestCase
{
  public function testRegular() {
    $this->assertContains('Example Domain', Downloaders::downloadPage('http://example.com/'));
  }

  public function testCloudflare() {
    $this->assertContains('Watch anime online, English anime online - Gogoanime', Downloaders::downloadPage('http://gogoanime.in/'));
  }

  // public function testJavascript() {
  //   $this->assertContains('Your Anime community entertainment center.', Downloaders::downloadPage('https://htvanime.com/'));
  // }

  // public function testScrolled() {
  //   $this->assertContains('Zoku Natsume Yuujinchou: 3D Nyanko-sensei Gekijou', Downloaders::downloadPage('https://myanimelist.net/animelist/Wicloz'));
  // }
}
