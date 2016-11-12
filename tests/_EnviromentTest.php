<?php

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

use App\AnimeSentinel\Downloaders;

class EnviromentTest extends TestCase
{
  public function testDownloadRegular() {
    $this->assertContains('Example Domain', Downloaders::downloadPage('http://example.com/'));
  }

  public function testDownloadCloudflare() {
    $this->assertContains('Watch anime online, English anime online - Gogoanime', Downloaders::downloadPage('http://gogoanime.in/'));
  }

  // public function testDownloadJavascript() {
  //   $this->assertContains('Your Anime community entertainment center.', Downloaders::downloadPage('https://htvanime.com/'));
  // }

  // public function testDownloadScrolled() {
  //   $this->assertContains('Zoku Natsume Yuujinchou: 3D Nyanko-sensei Gekijou', Downloaders::downloadPage('https://myanimelist.net/animelist/Wicloz'));
  // }

  public function testVideoMeta() {
    $meta = preg_replace('[\\s]', '', shell_exec('ffprobe -v quiet -print_format json -show_streams -show_format "http://www.w3schools.com/html/mov_bbb.mp4"'));
    $this->assertContains('"filename":"http://www.w3schools.com/html/mov_bbb.mp4"', $meta);
    $this->assertContains('"duration":"10.026667"', $meta);
	$this->assertContains('"format_name":"mov,mp4,m4a,3gp,3g2,mj2"', $meta);
    $this->assertContains('"width":320,"height":176,"coded_width":320,"coded_height":176,', $meta);
  }
}
