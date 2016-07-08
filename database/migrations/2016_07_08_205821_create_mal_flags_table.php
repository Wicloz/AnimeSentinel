<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMalFlagsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mal_flags', function (Blueprint $table) {
            $table->integer('mal_id')->unsigned();
            $table->boolean('is_hentai');
            $table->boolean('is_music');
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
        Schema::drop('mal_flags');
    }
}
