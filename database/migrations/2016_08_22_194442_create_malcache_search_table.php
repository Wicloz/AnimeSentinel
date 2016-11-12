<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMalcacheSearchTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('malcache_search', function (Blueprint $table) {
            $table->string('query');
            $table->text('results')->nullable()->default(null);
            $table->dateTime('cache_updated_at')->nullable()->default(null);
            $table->timestamps();
            $table->primary('query');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('malcache_search');
    }
}
