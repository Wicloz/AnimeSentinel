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

            $table->text('viewsettings_overview')->nullable()->default(null);

            $table->string('mal_user')->default('');
            $table->string('mal_pass')->default('');
            $table->boolean('mal_canread')->default(false);
            $table->boolean('mal_canwrite')->default(false);

            $table->boolean('auto_watching_state')->default(false);

            $table->boolean('nots_mail_state')->default(true);
            $table->string('nots_mail_settings', 512)->default('{"watching":"both","completed":"none","onhold":"none","dropped":"none","plantowatch":"both"}');

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
        Schema::dropIfExists('users');
    }
}
