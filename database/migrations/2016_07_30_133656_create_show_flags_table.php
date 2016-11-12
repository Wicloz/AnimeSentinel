<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateShowFlagsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('show_flags', function (Blueprint $table) {
            $table->integer('mal_id')->unsigned();
            $table->string('alt_rules', 4096)->default('{}');
            $table->boolean('check_youtube')->default(false);
            $table->timestamps();
            $table->primary('mal_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('show_flags');
    }
}
