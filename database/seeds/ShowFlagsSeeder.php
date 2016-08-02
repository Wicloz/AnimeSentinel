<?php

use Illuminate\Database\Seeder;
use App\ShowFlag;

class ShowFlagsSeeder extends Seeder
{
  /**
   * Run the database seeds.
   *
   * @return void
   */
  public function run()
  {
    $gate_s2 = ShowFlag::findOrNew(31637);
    $gate_s2->mal_id = 31637;
    $gate_s2->alt_rules = [
      "GATE" => "-",
      "Gate: Jieitai Kanochi nite" => "-",
      "Kaku Tatakaeri - Enryuu-hen" => "-",
      "Gate: Jieitai Kanochi nite, Kaku Tatakaeri - Enryuu-hen" => "+",
      "GATE S2" => "+",
    ];
    $gate_s2->save();

    $momokuri = ShowFlag::findOrNew(30014);
    $momokuri->mal_id = 30014;
    $momokuri->alt_rules = [
      "Momokuri 1" => "+",
      "Momokuri TV" => "+",
    ];
    $momokuri->save();

    $annehappy = ShowFlag::findOrNew(31080);
    $annehappy->mal_id = 31080;
    $annehappy->alt_rules = [
      "Unhappy" => "+",
    ];
    $annehappy->save();

    $arslansenki_s2 = ShowFlag::findOrNew(31821);
    $arslansenki_s2->mal_id = 31821;
    $arslansenki_s2->alt_rules = [
      "Arslan Senki (TV) S2" => "+",
    ];
    $arslansenki_s2->save();

    $xxxholic_movie = ShowFlag::findOrNew(793);
    $xxxholic_movie->mal_id = 793;
    $xxxholic_movie->alt_rules = [
      "xxxHOLiC: Manatsu no Yoru no Yume" => "+",
    ];
    $xxxholic_movie->save();

    $psi = ShowFlag::findOrNew(19469);
    $psi->mal_id = 19469;
    $psi->alt_rules = [
      "Saiki Kusuo no Psi Nan" => "-",
      "Saiki Kusuo no Ψ-nan" => "-",
      "Saiki Kusuo no nan" => "+",
    ];
    $psi->save();

    $psi_s2 = ShowFlag::findOrNew(33255);
    $psi_s2->mal_id = 33255;
    $psi_s2->alt_rules = [
      "Saiki Kusuo no Psi Nan (TV)" => "+",
    ];
    $psi_s2->save();

    $heroacademia = ShowFlag::findOrNew(31964);
    $heroacademia->mal_id = 31964;
    $heroacademia->alt_rules = [
      "Boku no Hero Academia: My Hero Academia" => "+",
    ];
    $heroacademia->save();

    $activeraidkidoukyoushuushitsu_s2 = ShowFlag::findOrNew(32301);
    $activeraidkidoukyoushuushitsu_s2->mal_id = 32301;
    $activeraidkidoukyoushuushitsu_s2->alt_rules = [
      "Active Raid: Kidou Kyoushuushitsu Dai Hachi Gakari 2nd" => "+",
    ];
    $activeraidkidoukyoushuushitsu_s2->save();

    $danganronpa3futurearc = ShowFlag::findOrNew(32189);
    $danganronpa3futurearc->mal_id = 32189;
    $danganronpa3futurearc->alt_rules = [
      "Danganronpa 3 - Future Arc" => "+",
    ];
    $danganronpa3futurearc->save();
  }
}
