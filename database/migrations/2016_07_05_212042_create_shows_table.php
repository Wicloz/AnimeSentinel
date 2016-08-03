<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateShowsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shows', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('mal_id')->unsigned()->nullable()->default(null)->unique();
            $table->string('thumbnail_id')->nullable()->default(null);
            $table->string('title');
            $table->string('alts', 4096)->default('[]');
            $table->text('description');
            $table->enum('type', [
              'tv',
              'ova',
              'ona',
              'movie',
              'special',
            ])->nullable()->default(null);
            $table->string('genres', 512)->default('[]');
            $table->integer('episode_amount')->nullable()->default(null);
            $table->integer('episode_duration')->nullable()->default(null);
            $table->date('airing_start')->nullable()->default(null);
            $table->date('airing_end')->nullable()->default(null);
            $table->bigInteger('hits')->default(0);
            $table->boolean('videos_initialised')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('shows');
    }
}
