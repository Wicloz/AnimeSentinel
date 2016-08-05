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
    $gates2 = ShowFlag::findOrNew(31637);
    $gates2->mal_id = 31637;
    $gates2->alt_rules = [
      "Gate: Jieitai Kanochi nite" => "-",
      "Kaku Tatakaeri - Enryuu-hen" => "-",
      "Gate: Jieitai Kanochi nite, Kaku Tatakaeri - Enryuu-hen" => "+",
      "GATE" => "-",
      "GATE S2" => "+",
    ];
    $gates2->save();

    $momokuri = ShowFlag::findOrNew(30014);
    $momokuri->mal_id = 30014;
    $momokuri->alt_rules = [
      "Momokuri (TV)" => "+",
    ];
    $momokuri->save();

    $arslansenkis2 = ShowFlag::findOrNew(31821);
    $arslansenkis2->mal_id = 31821;
    $arslansenkis2->alt_rules = [
      "Arslan Senki (TV) S2" => "+",
    ];
    $arslansenkis2->save();

    $xxxholicmovie = ShowFlag::findOrNew(793);
    $xxxholicmovie->mal_id = 793;
    $xxxholicmovie->alt_rules = [
      "xxxHOLiC: Manatsu no Yoru no Yume" => "+",
    ];
    $xxxholicmovie->save();

    $psis2 = ShowFlag::findOrNew(33255);
    $psis2->mal_id = 33255;
    $psis2->alt_rules = [
      "Saiki Kusuo no Psi Nan" => "-",
      "Saiki Kusuo no Psi Nan (TV)" => "+",
    ];
    $psis2->save();

    $activeraidkidoukyoushuushitsus2 = ShowFlag::findOrNew(32301);
    $activeraidkidoukyoushuushitsus2->mal_id = 32301;
    $activeraidkidoukyoushuushitsus2->alt_rules = [
      "Active Raid: Kidou Kyoushuushitsu Dai Hachi Gakari 2nd Season" => "+",
    ];
    $activeraidkidoukyoushuushitsus2->save();

    $danganronpa3futurearc = ShowFlag::findOrNew(32189);
    $danganronpa3futurearc->mal_id = 32189;
    $danganronpa3futurearc->alt_rules = [
      "Danganronpa 3 - Future Arc" => "+",
    ];
    $danganronpa3futurearc->save();

    $danganronpa3despairearc = ShowFlag::findOrNew(33028);
    $danganronpa3despairearc->mal_id = 33028;
    $danganronpa3despairearc->alt_rules = [
      "Danganronpa 3 - Despair Arc" => "+",
    ];
    $danganronpa3despairearc->save();

    $yugioharcv = ShowFlag::findOrNew(21639);
    $yugioharcv->mal_id = 21639;
    $yugioharcv->alt_rules = [
      "Yugioh" => "-",
    ];
    $yugioharcv->save();

    $nariagirls = ShowFlag::findOrNew(33394);
    $nariagirls->mal_id = 33394;
    $nariagirls->alt_rules = [
      "Mahou Shoujo Naria Girls" => "+",
    ];
    $nariagirls->save();

    $destinydeoxys = ShowFlag::findOrNew(1122);
    $destinydeoxys->mal_id = 1122;
    $destinydeoxys->alt_rules = [
      "Destiny Deoxys" => "+",
    ];
    $destinydeoxys->save();
  }
}
