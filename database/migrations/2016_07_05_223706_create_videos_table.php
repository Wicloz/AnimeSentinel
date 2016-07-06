<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateVideosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('videos', function (Blueprint $table) {
            $table->increments('id');

            $table->string('streamer_id');
            $table->integer('show_id')->unsigned();
            $table->enum('translation_type', ['sub', 'dub']);

            $table->integer('episode_num');
            $table->dateTime('uploadtime');
            $table->string('link_stream', 1024);
            $table->string('link_episode', 1024);

            $table->bigInteger('hits')->default(0);
            $table->string('link_video', 2048);
            $table->string('resolution');

            $table->timestamps();

            $table->index(['show_id', 'translation_type', 'episode_num', 'streamer_id']);
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
        Schema::drop('videos');
    }
}
