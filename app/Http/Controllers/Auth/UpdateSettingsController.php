<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests;
use Illuminate\Support\Facades\Auth;

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
    $this->validate($request, [
      'username' => ['max:255'],
      'email' => ['email', 'max:255'],
      'mal_user' => ['max:255'],
      'mal_pass' => ['max:255'],
    ]);
    if ($request->username !== Auth::user()->username) {
      $this->validate($request, [
        'username' => ['unique:users'],
      ]);
    }
    if ($request->email !== Auth::user()->email) {
      $this->validate($request, [
        'email' => ['unique:users'],
      ]);
    }

    $texts = ['username', 'email', 'mal_user', 'mal_pass'];
    $checkboxes = ['nots_mail_state', 'auto_watching'];

    foreach ($texts as $attribute) {
      if (isset($request->$attribute) && $request->$attribute !== '') {
        Auth::user()->$attribute = $request->$attribute;
      }
    }
    foreach ($checkboxes as $attribute) {
      if (!empty($request->$attribute)) {
        Auth::user()->$attribute = true;
      } else {
        Auth::user()->$attribute = false;
      }
    }
    Auth::user()->save();

    return back();
  }

  /**
   * Update the user's password data.
   *
   * @return \Illuminate\Http\Response
   */
  public function password(Request $request) {
    $this->validate($request, [
      'current_password' => ['required', 'password'],
      'new_password' => ['required', 'min:8', 'confirmed'],
    ]);

    Auth::user()->password = bcrypt($request->new_password);
    Auth::user()->save();

    return back();
  }

  /**
   * Update the user's mail notification settings.
   *
   * @return \Illuminate\Http\Response
   */
  public function notifications_mail(Request $request) {
    return back();
  }
}
