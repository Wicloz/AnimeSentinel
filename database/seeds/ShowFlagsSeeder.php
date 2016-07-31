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
  }
}
