<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateStreamersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('streamers', function (Blueprint $table) {
            $table->string('id');
            $table->string('name');
            $table->bigInteger('popularity')->default(0);
            $table->string('link_home', 1024);
            $table->string('link_recent', 1024);
            $table->timestamps();
            $table->primary('id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('streamers');
    }
}
