<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Queue;
use Illuminate\Queue\Events\JobFailed;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
      Queue::failing(function (JobFailed $event) {
        // TODO: send email
        // $event->connectionName
        // $event->job
        // $event->data
      });
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {

    }
}
