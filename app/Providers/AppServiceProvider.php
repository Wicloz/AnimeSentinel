<?php

namespace App\Providers;

use Queue;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Validator;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AppServiceProvider extends ServiceProvider
{
  /**
   * Bootstrap any application services.
   *
   * @return void
   */
  public function boot() {
    Queue::failing(function (JobFailed $event) {
      \Mail::send('emails.report_general', ['description' => 'Job Failed', 'vars' => [
        'Data' => json_encode($event->job->payload()),
        'Exception' => $event->exception,
      ]], function ($m) {
        $m->subject('AnimeSentinel Job Fail Report');
        $m->from('reports.animesentinel@wilcodeboer.me', 'AnimeSentinel Reports');
        $m->to('animesentinel@wilcodeboer.me');
      });

      if (!str_ends_with($event->job->payload()['data']['commandName'], 'FindRecentVideos') && !str_ends_with($event->job->payload()['data']['commandName'], 'UserPeriodicTasks')) {
        $job = unserialize($event->job->payload()['data']['command']);
        queueJob($job, $job->queue);
      }
    });

    Validator::extend('password', function ($attribute, $value, $parameters, $validator) {
      return Auth::check() ? Hash::check($value, Auth::user()->password) : false;
    });
  }

  /**
   * Register any application services.
   *
   * @return void
   */
  public function register() {

  }
}
