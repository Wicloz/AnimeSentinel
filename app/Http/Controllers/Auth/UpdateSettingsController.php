<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;

class UpdateSettingsController extends Controller
{
  /**
   * Create a new controller instance.
   *
   * @return void
   */
  public function __construct() {
    $this->middleware('auth');
  }

  /**
   * Update the user's general data.
   *
   * @return \Illuminate\Http\Response
   */
  public function general(Request $request) {

  }

  /**
   * Update the user's password data.
   *
   * @return \Illuminate\Http\Response
   */
  public function password(Request $request) {

  }

  /**
   * Update the user's mail notification settings.
   *
   * @return \Illuminate\Http\Response
   */
  public function notifications_mail(Request $request) {

  }
}
