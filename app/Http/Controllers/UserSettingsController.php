<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;

class UserSettingsController extends Controller
{
  /**
   * Show a settings page for the user's general data.
   *
   * @return \Illuminate\Http\Response
   */
  public function general() {
    return view('users.settings.general');
  }

  /**
   * Show a settings page for the mail notification settings.
   *
   * @return \Illuminate\Http\Response
   */
  public function notifications_mail() {
    return view('users.settings.notifications.mail');
  }
}
