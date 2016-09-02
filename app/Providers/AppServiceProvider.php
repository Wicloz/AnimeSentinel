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
    });

    Validator::extend('password', function ($attribute, $value, $parameters, $validator) {
      return Hash::check($value, Auth::user()->password);
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
