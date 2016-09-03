<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use Illuminate\Support\Facades\Auth;

class UserSettingsController extends Controller
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
   * Show a settings page for the user's general data.
   *
   * @return \Illuminate\Http\Response
   */
  public function general() {
    return view('users.settings.general');
  }

  /**
   * Show a settings page for the user's mail notification settings.
   *
   * @return \Illuminate\Http\Response
   */
  public function notifications_mail(Request $request) {
    $mal_list = null;
    if (!empty($request->status)) {
      $mal_list = Auth::user()->mal_list->where('status', $request->status);
    }
    return view('users.settings.notifications.mail', [
      'mallist' => $mal_list,
      'loadedStatus' => $request->status,
    ]);
  }
}
