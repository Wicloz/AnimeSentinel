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
    $this->assertEquals('{"streams":[{"index":0,"codec_name":"h264","codec_long_name":"H.264/AVC/MPEG-4AVC/MPEG-4part10","profile":"Main","codec_type":"video","codec_time_base":"1/180000","codec_tag_string":"avc1","codec_tag":"0x31637661","width":320,"height":176,"coded_width":320,"coded_height":176,"has_b_frames":2,"sample_aspect_ratio":"0:1","display_aspect_ratio":"0:1","pix_fmt":"yuv420p","level":12,"color_range":"tv","color_space":"smpte170m","color_transfer":"bt709","color_primaries":"smpte170m","chroma_location":"left","refs":4,"is_avc":"1","nal_length_size":"4","r_frame_rate":"25/1","avg_frame_rate":"25/1","time_base":"1/90000","start_pts":0,"start_time":"0.000000","duration_ts":900000,"duration":"10.000000","bit_rate":"300570","bits_per_raw_sample":"8","nb_frames":"250","disposition":{"default":1,"dub":0,"original":0,"comment":0,"lyrics":0,"karaoke":0,"forced":0,"hearing_impaired":0,"visual_impaired":0,"clean_effects":0,"attached_pic":0},"tags":{"creation_time":"2012-03-1308:58:06","language":"und","encoder":"JVT/AVCCoding"}},{"index":1,"codec_name":"aac","codec_long_name":"AAC(AdvancedAudioCoding)","profile":"LC","codec_type":"audio","codec_time_base":"1/48000","codec_tag_string":"mp4a","codec_tag":"0x6134706d","sample_fmt":"fltp","sample_rate":"48000","channels":2,"channel_layout":"stereo","bits_per_sample":0,"r_frame_rate":"0/0","avg_frame_rate":"0/0","time_base":"1/48000","start_pts":0,"start_time":"0.000000","duration_ts":481280,"duration":"10.026667","bit_rate":"160545","max_bit_rate":"175528","nb_frames":"470","disposition":{"default":1,"dub":0,"original":0,"comment":0,"lyrics":0,"karaoke":0,"forced":0,"hearing_impaired":0,"visual_impaired":0,"clean_effects":0,"attached_pic":0},"tags":{"creation_time":"2012-03-1308:58:06","language":"und"}},{"index":2,"codec_name":"aac","codec_long_name":"AAC(AdvancedAudioCoding)","profile":"LC","codec_type":"audio","codec_time_base":"1/48000","codec_tag_string":"mp4a","codec_tag":"0x6134706d","sample_fmt":"fltp","sample_rate":"48000","channels":2,"channel_layout":"stereo","bits_per_sample":0,"r_frame_rate":"0/0","avg_frame_rate":"0/0","time_base":"1/48000","start_pts":0,"start_time":"0.000000","duration_ts":481280,"duration":"10.026667","bit_rate":"160580","max_bit_rate":"175512","nb_frames":"470","disposition":{"default":0,"dub":0,"original":0,"comment":0,"lyrics":0,"karaoke":0,"forced":0,"hearing_impaired":0,"visual_impaired":0,"clean_effects":0,"attached_pic":0},"tags":{"creation_time":"2012-03-1308:58:06","language":"und"}},{"index":3,"codec_name":"mov_text","codec_long_name":"3GPPTimedTextsubtitle","codec_type":"subtitle","codec_time_base":"1/90000","codec_tag_string":"text","codec_tag":"0x74786574","r_frame_rate":"0/0","avg_frame_rate":"0/0","time_base":"1/90000","start_pts":0,"start_time":"0.000000","duration_ts":902400,"duration":"10.026667","bit_rate":"18","nb_frames":"1","disposition":{"default":0,"dub":0,"original":0,"comment":0,"lyrics":0,"karaoke":0,"forced":0,"hearing_impaired":0,"visual_impaired":0,"clean_effects":0,"attached_pic":0},"tags":{"creation_time":"2012-03-1308:58:06","language":"und"}}],"format":{"filename":"http://www.w3schools.com/html/mov_bbb.mp4","nb_streams":4,"nb_programs":0,"format_name":"mov,mp4,m4a,3gp,3g2,mj2","format_long_name":"QuickTime/MOV","start_time":"0.000000","duration":"10.026667","size":"788493","bit_rate":"629116","probe_score":100,"tags":{"major_brand":"mp42","minor_version":"0","compatible_brands":"mp42isomavc1","creation_time":"2012-03-1308:58:06","encoder":"HandBrake0.9.62012022800"}}}',
      preg_replace('[\\s]', '', shell_exec('ffprobe -v quiet -print_format json -show_streams -show_format "http://www.w3schools.com/html/mov_bbb.mp4"')));
  }
}
