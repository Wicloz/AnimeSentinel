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
            $table->increments('id');                                      // video

            $table->integer('show_id')->unsigned();                        // episode
            $table->enum('translation_type', ['sub', 'dub']);              // episode
            $table->float('episode_num');                                  // episode

            $table->string('streamer_id');                                 // video
            $table->integer('mirror')->unsinged();                         // video

            $table->string('link_episode', 1024)->default('');             // episode

            $table->string('link_stream', 1024)->default('');              // video
            $table->string('notes')->default('');                          // video
            $table->bigInteger('hits')->default(0);                        // video
            $table->string('link_video', 2048)->default('');               // video

            $table->dateTime('uploadtime')->nullable()->default(null);     // video
            $table->string('resolution')->nullable()->default(null);       // video
            $table->float('duration')->nullable()->default(null);          // video
            $table->string('encoding')->default('video/mp4');              // video

            $table->timestamps();

            $table->unique(['show_id', 'translation_type', 'episode_num', 'streamer_id', 'mirror'], 'videos_video_identifier');
            $table->index(['show_id', 'translation_type', 'episode_num'], 'videos_episode_identifier');
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
