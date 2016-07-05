<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEpisodesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('episodes', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('show_id')->unsigned();
            $table->string('anime_type');
            $table->integer('episode_num');
            $table->string('streamer_id');
            $table->string('link', 1024);
            $table->string('videolink', 2048);
            $table->dateTime('uploadtime');
            $table->string('resolutions');
            $table->timestamps();
            $table->unique(['show_id', 'anime_type', 'episode_num', 'streamer_id']);
            $table->foreign('show_id')->references('id')->on('shows')->onDelete('cascade');
            $table->foreign('streamer_id')->references('id')->on('streamers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('episodes');
    }
}
