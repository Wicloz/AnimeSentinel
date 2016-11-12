<?php

use Illuminate\Support\Facades\Schema;
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

            $table->text('remote_thumbnail_urls')->default('[]');
            $table->text('local_thumbnail_ids')->default('[]');

            $table->string('title');
            $table->string('alts', 4096)->default('[]');
            $table->text('description')->default('');

            $table->string('prequels', 4096)->default('[]');
            $table->string('sequels', 4096)->default('[]');
            $table->string('summaries', 4096)->default('[]');
            $table->string('specials', 4096)->default('[]');
            $table->string('alternatives', 4096)->default('[]');
            $table->string('others', 4096)->default('[]');

            $table->enum('type', [
              'tv',
              'ova',
              'ona',
              'movie',
              'special',
              'music',
            ])->nullable()->default(null);
            $table->string('genres', 512)->default('[]');
            $table->string('season')->nullable()->default(null);
            $table->enum('rating', [
              'G',
              'PG',
              'PG-13',
              'R',
              'R+',
            ])->nullable()->default(null);
            $table->integer('episode_amount')->nullable()->default(null);
            $table->integer('episode_duration')->nullable()->default(null);

            $table->date('airing_start')->nullable()->default(null);
            $table->date('airing_end')->nullable()->default(null);
            $table->time('airing_time')->nullable()->default(null);
            $table->enum('airing_type', [
              'weekly',
              'irregular',
            ])->nullable()->default(null);

            $table->bigInteger('hits')->unsigned()->default(0);
            $table->boolean('videos_initialised')->default(false);
            $table->dateTime('cache_updated_at')->nullable()->default(null);

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
        Schema::dropIfExists('shows');
    }
}
