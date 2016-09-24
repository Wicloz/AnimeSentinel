<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
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
   * Show the page containing an overview of the user's anime list.
   *
   * @return \Illuminate\Http\Response
   */
  public function overview() {
    return view('users.overview');
  }

  /**
   * Show a settings page for the user's general data.
   *
   * @return \Illuminate\Http\Response
   */
  public function settings_general() {
    return view('users.settings.general');
  }

  /**
   * Show a settings page for the user's mail notification settings.
   *
   * @return \Illuminate\Http\Response
   */
  public function settings_nots_mail(Request $request) {
    return view('users.settings.notifications.mail', [
      'mal_list' => !empty($request->status) ? Auth::user()->malFields()->pluck('mal_show')->where('status', $request->status) : null,
      'loadedStatus' => $request->status,
    ]);
  }
}
