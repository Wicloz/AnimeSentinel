<?php

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
            $table->text('alt_rules');
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
        Schema::drop('show_flags');
    }
}
