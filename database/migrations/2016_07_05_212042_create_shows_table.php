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
            $table->integer('mal_id')->default(-1)->index();
            $table->string('thumbnail_id');
            $table->string('title');
            $table->text('alts');
            $table->text('description');
            $table->enum('show_type', [
              'tv',
              'ova',
              'ona',
              'movie',
              'special',
            ]);
            $table->text('genres');
            $table->integer('episode_amount')->default(-1);
            $table->integer('episode_duration')->default(-1);
            $table->bigInteger('hits')->default(0);
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
