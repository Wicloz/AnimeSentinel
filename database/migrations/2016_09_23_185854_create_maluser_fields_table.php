<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMaluserFieldsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('maluser_fields', function (Blueprint $table) {
            $table->integer('mal_id')->unsigned();
            $table->integer('user_id')->unsigned();

            $table->text('mal_show')->nullable();

            $table->string('nots_mail_setting')->nullable()->default(null);
            $table->string('nots_mail_notified', 10240)->default('[]');

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
        Schema::dropIfExists('maluser_fields');
    }
}
