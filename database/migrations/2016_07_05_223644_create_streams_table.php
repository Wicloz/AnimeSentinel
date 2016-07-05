<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateStreamsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('streams', function (Blueprint $table) {
            $table->string('streamer_id');
            $table->integer('show_id')->unsigned();
            $table->string('anime_type');
            $table->string('link', 1024);
            $table->timestamps();
            $table->primary(['streamer_id', 'show_id', 'anime_type']);
            $table->foreign('streamer_id')->references('id')->on('streamers')->onDelete('cascade');
            $table->foreign('show_id')->references('id')->on('shows')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('streams');
    }
}
