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

            $table->text('mal_show')->nullable()->default(null);

            $table->boolean('auto_watching_changed')->default(false);

            $table->string('nots_mail_setting')->nullable()->default(null);
            $table->text('nots_mail_notified_sub')->default('[]');
            $table->text('nots_mail_notified_dub')->default('[]');

            $table->timestamps();

            $table->primary(['mal_id', 'user_id']);
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
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
