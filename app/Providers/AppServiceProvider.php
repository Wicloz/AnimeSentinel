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
    setlocale(LC_ALL, 'en_US.UTF-8');
    putenv('LC_ALL=en_US.UTF-8');
    putenv('PYTHONIOENCODING=UTF-8');
    putenv('PATH='.resource_path('binaries').PATH_SEPARATOR.exec('echo $PATH'));

    Queue::failing(function (JobFailed $event) {
      try {
        \Mail::send('emails.reports.general', ['description' => 'Job Failed', 'vars' => [
          'Data' => json_encode($event->job->payload()),
          'Exception' => $event->exception,
        ]], function ($m) {
          $m->subject('AnimeSentinel Job Fail Report');
          $m->from('reports.animesentinel@wilcodeboer.me', 'AnimeSentinel Reports');
          $m->to(config('mail.debug_addresses'));
        });
      } catch (\Exception $e) {}

      if (config('queue.default') !== 'sync' && !str_ends_with($event->job->payload()['data']['commandName'], 'FindRecentVideos') && !str_ends_with($event->job->payload()['data']['commandName'], 'UserPeriodicTasks')) {
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
