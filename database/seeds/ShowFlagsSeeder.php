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
  public function run() {
    DB::table('show_flags')->delete();

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

    $psi = ShowFlag::findOrNew(19469);
    $psi->mal_id = 19469;
    $psi->alt_rules = [
      "Saiki Kusuo no Psi Nan" => "-",
      "Saiki Kusuo no Ψ nan" => "+",
    ];
    $psi->save();

    $psis2 = ShowFlag::findOrNew(33255);
    $psis2->mal_id = 33255;
    $psis2->alt_rules = [
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

    $danganronpa3hopearc = ShowFlag::findOrNew(34103);
    $danganronpa3hopearc->mal_id = 34103;
    $danganronpa3hopearc->alt_rules = [
      "Danganronpa 3 - Hope Arc" => "+",
    ];
    $danganronpa3hopearc->save();

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

    $berserk2016 = ShowFlag::findOrNew(32379);
    $berserk2016->mal_id = 32379;
    $berserk2016->alt_rules = [
      "Berserk" => "-",
    ];
    $berserk2016->save();

    $pkmvolcanion = ShowFlag::findOrNew(31231);
    $pkmvolcanion->mal_id = 31231;
    $pkmvolcanion->alt_rules = [
      "Pokemon XY&Z: Volcanion to Karakuri no Magiana" => "+",
    ];
    $pkmvolcanion->save();

    $shokugekinosomas2 = ShowFlag::findOrNew(32282);
    $shokugekinosomas2->mal_id = 32282;
    $shokugekinosomas2->alt_rules = [
      "Shokugeki no Soma S2" => "+",
    ];
    $shokugekinosomas2->save();

    $honoonoalpenrose = ShowFlag::findOrNew(3807);
    $honoonoalpenrose->mal_id = 3807;
    $honoonoalpenrose->alt_rules = [
      "Honoo no Alpen Rose: Judy & Randy" => "+",
    ];
    $honoonoalpenrose->save();

    $gintamaaizomekaorihen = ShowFlag::findOrNew(32366);
    $gintamaaizomekaorihen->mal_id = 32366;
    $gintamaaizomekaorihen->alt_rules = [
      "Gintama: Aizome Kaori-hen" => "+",
    ];
    $gintamaaizomekaorihen->save();

    $summer = ShowFlag::findOrNew(1692);
    $summer->mal_id = 1692;
    $summer->alt_rules = [
      "Summer OVA" => "+",
    ];
    $summer->save();

    $kaijuugirls = ShowFlag::findOrNew(33011);
    $kaijuugirls->mal_id = 33011;
    $kaijuugirls->alt_rules = [
      "Kaijuu Girls" => "+",
    ];
    $kaijuugirls->save();

    $girlishnumber = ShowFlag::findOrNew(32607);
    $girlishnumber->mal_id = 32607;
    $girlishnumber->alt_rules = [
      "Girlish Number" => "+",
      "Giarlish Number" => "+",
    ];
    $girlishnumber->save();

    $honoonokokuinhome = ShowFlag::findOrNew(32415);
    $honoonokokuinhome->mal_id = 32415;
    $honoonokokuinhome->alt_rules = [
      "Garo: Honoo no Kouin - Home" => "+",
    ];
    $honoonokokuinhome->save();

    $bernardjouiwaku = ShowFlag::findOrNew(33462);
    $bernardjouiwaku->mal_id = 33462;
    $bernardjouiwaku->alt_rules = [
      "Bernard Jou Iwaku." => "+",
    ];
    $bernardjouiwaku->save();

    $hibikeeuphonium2 = ShowFlag::findOrNew(31988);
    $hibikeeuphonium2->mal_id = 31988;
    $hibikeeuphonium2->alt_rules = [
      "Hibike! Euphonium 2nd Season" => "+",
    ];
    $hibikeeuphonium2->save();

    $cranegamegirls2 = ShowFlag::findOrNew(33541);
    $cranegamegirls2->mal_id = 33541;
    $cranegamegirls2->alt_rules = [
      "Bishoujo Yuugi Unit Crane Game Girls 2nd Season" => "+",
    ];
    $cranegamegirls2->save();

    $natsumeyuujinchou5 = ShowFlag::findOrNew(32983);
    $natsumeyuujinchou5->mal_id = 32983;
    $natsumeyuujinchou5->alt_rules = [
      "Natsume Yuujinchou 5" => "+",
    ];
    $natsumeyuujinchou5->save();

    $kaitoujoker4 = ShowFlag::findOrNew(33490);
    $kaitoujoker4->mal_id = 33490;
    $kaitoujoker4->alt_rules = [
      "Kaitou Joker Season 4" => "+",
    ];
    $kaitoujoker4->save();

    $okusamagaseitokaichou2 = ShowFlag::findOrNew(32603);
    $okusamagaseitokaichou2->mal_id = 32603;
    $okusamagaseitokaichou2->alt_rules = [
      "Okusama ga Seito Kaichou! 2nd Season" => "+",
    ];
    $okusamagaseitokaichou2->save();

    $showbyrock2 = ShowFlag::findOrNew(32038);
    $showbyrock2->mal_id = 32038;
    $showbyrock2->alt_rules = [
      "Show By Rock!! 2nd Season" => "+",
    ];
    $showbyrock2->save();

    $utanoprincesama4 = ShowFlag::findOrNew(31178);
    $utanoprincesama4->mal_id = 31178;
    $utanoprincesama4->alt_rules = [
      "Uta no Prince Sama 4" => "+",
    ];
    $utanoprincesama4->save();

    $sidonianokishimovie = ShowFlag::findOrNew(28495);
    $sidonianokishimovie->mal_id = 28495;
    $sidonianokishimovie->alt_rules = [
      "Knights of Sidonia Movie" => "+",
    ];
    $sidonianokishimovie->save();

    $aoharukikanjuuspecial = ShowFlag::findOrNew(31754);
    $aoharukikanjuuspecial->mal_id = 31754;
    $aoharukikanjuuspecial->alt_rules = [
      "Aoharu x Kikanjuu Special" => "+",
    ];
    $aoharukikanjuuspecial->save();

    $zaregotoseries = ShowFlag::findOrNew(33263);
    $zaregotoseries->mal_id = 33263;
    $zaregotoseries->alt_rules = [
      "Zaregoto Series" => "+",
    ];
    $zaregotoseries->save();

    $magicaquartetxnisioisin = ShowFlag::findOrNew(23831);
    $magicaquartetxnisioisin->mal_id = 23831;
    $magicaquartetxnisioisin->alt_rules = [
      "MAGICA QUARTET x NISIOISIN" => "+",
    ];
    $magicaquartetxnisioisin->save();

    $ameirococoa3 = ShowFlag::findOrNew(33245);
    $ameirococoa3->mal_id = 33245;
    $ameirococoa3->alt_rules = [
      "Ame-iro Cocoa 3" => "+",
    ];
    $ameirococoa3->save();

    $tanoshiimuuminikka = ShowFlag::findOrNew(2150);
    $tanoshiimuuminikka->mal_id = 2150;
    $tanoshiimuuminikka->alt_rules = [
      "Tanoshii Moomin Ikka" => "+",
    ];
    $tanoshiimuuminikka->save();

    $koutetsutenshikurumizero = ShowFlag::findOrNew(556);
    $koutetsutenshikurumizero->mal_id = 556;
    $koutetsutenshikurumizero->alt_rules = [
      "Steel Angel Kurumi Zero" => "+",
    ];
    $koutetsutenshikurumizero->save();

    $imasokoniiruboku = ShowFlag::findOrNew(160);
    $imasokoniiruboku->mal_id = 160;
    $imasokoniiruboku->alt_rules = [
      "Now and Then" => "-",
      "Here and There" => "-",
      "Now and Then, Here and There" => "+",
      "Ima" => "-",
      "Soko ni Iru Boku" => "-",
      "Ima, Soko ni Iru Boku" => "+",
    ];
    $imasokoniiruboku->save();

    $mahounostarmagicalemi = ShowFlag::findOrNew(2038);
    $mahounostarmagicalemi->mal_id = 2038;
    $mahounostarmagicalemi->alt_rules = [
      "Magica Emi" => "+",
    ];
    $mahounostarmagicalemi->save();

    $detectiveconanepisodeone = ShowFlag::findOrNew(34036);
    $detectiveconanepisodeone->mal_id = 34036;
    $detectiveconanepisodeone->alt_rules = [
      "Detective Conan: Episode One" => "+",
    ];
    $detectiveconanepisodeone->save();

    $talesofzestiriathex2017 = ShowFlag::findOrNew(34086);
    $talesofzestiriathex2017->mal_id = 34086;
    $talesofzestiriathex2017->alt_rules = [
      "Tales of Zestiria the X 2" => "+",
      "Tales of Zestiria the X S2" => "+",
    ];
    $talesofzestiriathex2017->save();

    $shingekinokyojinseason2 = ShowFlag::findOrNew(25777);
    $shingekinokyojinseason2->mal_id = 25777;
    $shingekinokyojinseason2->alt_rules = [
      "Shingeki no Kyojin 2nd Season" => "+",
    ];
    $shingekinokyojinseason2->save();

    $kochinpa = ShowFlag::findOrNew(32400);
    $kochinpa->mal_id = 32400;
    $kochinpa->alt_rules = [
      "Kochin Pa!" => "+",
    ];
    $kochinpa->save();

    $aonoexorcistkyotofujououhen = ShowFlag::findOrNew(33506);
    $aonoexorcistkyotofujououhen->mal_id = 33506;
    $aonoexorcistkyotofujououhen->alt_rules = [
      "Ao no Exorcist 2" => "+",
    ];
    $aonoexorcistkyotofujououhen->save();

    $shouwagenrokurakugoshinjuusukerokufutatabihen = ShowFlag::findOrNew(33095);
    $shouwagenrokurakugoshinjuusukerokufutatabihen->mal_id = 33095;
    $shouwagenrokurakugoshinjuusukerokufutatabihen->alt_rules = [
      "Shouwa Genroku Rakugo Shinjuu 2" => "+",
    ];
    $shouwagenrokurakugoshinjuusukerokufutatabihen->save();

    $yowamushipedalnewgeneration = ShowFlag::findOrNew(31783);
    $yowamushipedalnewgeneration->mal_id = 31783;
    $yowamushipedalnewgeneration->alt_rules = [
      "Yowamushi Pedal 3" => "+",
    ];
    $yowamushipedalnewgeneration->save();

    $dragonballzplantoeradicatesupersaiyansovaremake = ShowFlag::findOrNew(10017);
    $dragonballzplantoeradicatesupersaiyansovaremake->mal_id = 10017;
    $dragonballzplantoeradicatesupersaiyansovaremake->alt_rules = [
      "Dragon Ball Z: Plan to Eradicate Super Saiyans" => "+",
    ];
    $dragonballzplantoeradicatesupersaiyansovaremake->save();

    $sidonianokishidaikyuuwakuseiseneki = ShowFlag::findOrNew(24893);
    $sidonianokishidaikyuuwakuseiseneki->mal_id = 24893;
    $sidonianokishidaikyuuwakuseiseneki->alt_rules = [
      "Knights of Sidonia Season 2" => "+",
    ];
    $sidonianokishidaikyuuwakuseiseneki->save();

    $macrossdsp = ShowFlag::findOrNew(33807);
    $macrossdsp->mal_id = 33807;
    $macrossdsp->alt_rules = [
      "Macross Delta Special" => "+",
    ];
    $macrossdsp->save();

    $gintama2017 = ShowFlag::findOrNew(34096);
    $gintama2017->mal_id = 34096;
    $gintama2017->alt_rules = [
      "Gintama. (2017)" => "+",
    ];
    $gintama2017->save();

    $aimaimiisurgicalfriends = ShowFlag::findOrNew(34295);
    $aimaimiisurgicalfriends->mal_id = 34295;
    $aimaimiisurgicalfriends->alt_rules = [
      "Ai Mai Mii Third season" => "+",
      "Ai Mai Mii: Surgical Friends" => "+",
    ];
    $aimaimiisurgicalfriends->save();

    $aimaimiimousoucatastrophespecial = ShowFlag::findOrNew(25439);
    $aimaimiimousoucatastrophespecial->mal_id = 25439;
    $aimaimiimousoucatastrophespecial->alt_rules = [
      "Ai Mai Mii: Mousou Catastrophe Special" => "+",
    ];
    $aimaimiimousoucatastrophespecial->save();

    $chaoschild = ShowFlag::findOrNew(30485);
    $chaoschild->mal_id = 30485;
    $chaoschild->alt_rules = [
      "Chaos;Child" => "+",
    ];
    $chaoschild->save();

    $rewrite2 = ShowFlag::findOrNew(34126);
    $rewrite2->mal_id = 34126;
    $rewrite2->alt_rules = [
      "Rewrite 2nd Season" => "+",
    ];
    $rewrite2->save();
  }
}
