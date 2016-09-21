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
            $table->boolean('mal_canread')->default(false);
            $table->boolean('mal_canwrite')->default(false);
            $table->text('mal_list')->nullable();

            $table->boolean('nots_mail_state')->default(true);
            $table->text('nots_mail_settings_state_general')->nullable();
            $table->text('nots_mail_settings_state_specific')->nullable();
            $table->text('nots_mail_settings_ttype_general')->nullable();
            $table->text('nots_mail_settings_ttype_specific')->nullable();
            $table->text('nots_mail_notified')->nullable();

            $table->boolean('auto_watching')->default(true);
            $table->text('auto_watching_changed')->nullable();

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
