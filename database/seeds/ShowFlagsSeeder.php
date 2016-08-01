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
  }
}
