<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');

            $table->string('username')->unique();
            $table->string('email')->unique();
            $table->string('password');

            $table->string('mal_user')->default('');
            $table->string('mal_pass')->default('');
            $table->string('mal_status', 512)->default('{}');
            $table->dateTime('mal_last_checked')->nullable()->default(null);

            $table->boolean('nots_mail_state')->default(true);
            $table->text('nots_mail_settings_general')->nullable();
            $table->text('nots_mail_settings_specific')->nullable();

            $table->boolean('auto_watching')->default(true);

            $table->rememberToken();
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
        Schema::drop('users');
    }
}
