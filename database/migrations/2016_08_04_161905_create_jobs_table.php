<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateJobsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('jobs', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->string('job_task')->default('');
            $table->string('show_id')->nullable()->default(null);
            $table->string('job_data')->default('null');
            $table->string('queue')->default('default');

            $table->longText('payload');
            $table->tinyInteger('attempts')->unsigned();
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');

            $table->index(['queue', 'reserved_at']);
            $table->unique(['show_id', 'job_task', 'job_data', 'reserved_at']);
        });

        if (config('queue.default') === 'database')
        {
          queueJob(new \App\Jobs\FindRecentVideos(), 'periodic_low');
          queueJob(new \App\Jobs\UserPeriodicTasks(), 'periodic_low');
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('jobs');
    }
}
