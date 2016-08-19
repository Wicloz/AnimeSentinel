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
        \Mail::send('emails.report_bug', ['description' => 'Job Failed', 'vars' => [
          'Job' => $event->job,
          'Data' => $event->data,
        ]], function ($m) {
          $m->subject('AnimeSentinel Job Fail Report');
          $m->from('reports.animesentinel@wilcodeboer.me', 'AnimeSentinel Reports');
          $m->to('animesentinel@wilcodeboer.me');
        });
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
