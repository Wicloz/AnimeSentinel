<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

use Carbon\Carbon;
use App\User;

class UserPeriodicTasks implements ShouldQueue
{
  use InteractsWithQueue, Queueable, SerializesModels;
  public $db_data;

  /**
   * Create a new job instance.
   *
   * @return void
   */
  public function __construct() {
    // Set special database data
    $this->db_data = [
      'job_task' => 'UserPeriodicTasks',
      'show_id' => null,
      'job_data' => null,
    ];
  }

  /**
   * Execute the job.
   *
   * @return void
   */
  public function handle() {
    User::orderBy('id')->chunk(100, function ($users) {
      foreach ($users as $user) {
        try {
          $user->periodicTasks();
        } catch (\Exception $e) {
          mailException('Failed to perform periodic tasks for a user', $e, [
            'Username' => $user->username,
            'Email' => $user->email,
            'MAL Username' => $user->mal_user,
          ]);
        }
      }
    });
    queueJob((new UserPeriodicTasks)->delay(Carbon::now()->addMinutes(10)), 'periodic_low');
  }

  /**
   * Handle a job failure.
   *
   * @return void
   */
  public function failed() {
    queueJob((new UserPeriodicTasks)->delay(Carbon::now()->addMinutes(10)), 'periodic_low');
  }
}
