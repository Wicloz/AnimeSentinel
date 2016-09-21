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

    $texts = ['username', 'email'];
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
   * Update the user's mal credentials and update the mal cache.
   *
   * @return \Illuminate\Http\Response
   */
  public function mal(Request $request) {
    $this->validate($request, [
      'mal_user' => ['max:255'],
      'mal_pass' => ['max:255'],
    ]);

    $mustUpdateMalCache =
      (isset($request->mal_user) && $request->mal_user !== '' && $request->mal_user !== Auth::user()->mal_user) ||
      (isset($request->mal_pass) && $request->mal_pass !== '' && $request->mal_pass !== Auth::user()->mal_pass)
    ;

    $texts = ['mal_user', 'mal_pass'];
    foreach ($texts as $attribute) {
      if (isset($request->$attribute) && $request->$attribute !== '') {
        Auth::user()->$attribute = $request->$attribute;
      }
    }
    Auth::user()->save();

    if ($mustUpdateMalCache || $request->update_mal_cache) {
      Auth::user()->updateCache();
    }

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
   * Update the general user's mail notification settings.
   *
   * @return \Illuminate\Http\Response
   */
  public function notifications_mail_general(Request $request) {
    Auth::user()->nots_mail_settings_state_general = [
      'watching' => !empty($request->notifications_watching),
      'completed' => !empty($request->notifications_completed),
      'onhold' => !empty($request->notifications_onhold),
      'dropped' => !empty($request->notifications_dropped),
      'plantowatch' => !empty($request->notifications_plantowatch),
    ];
    Auth::user()->save();

    return back();
  }

  /**
   * Update the specific user's mail notification settings.
   *
   * @return \Illuminate\Http\Response
   */
  public function notifications_mail_specific(Request $request) {
    $temp = Auth::user()->nots_mail_settings_state_specific;

    foreach ($request->all() as $key => $value) {
      if (str_starts_with($key, 'state_')) {
        $key = (int) str_replace('state_', '', $key);
        $value = json_decode($value);

        if ($value === null) {
          unset($temp[$key]);
        } else {
          $temp[$key] = $value;
        }
      }
    }

    Auth::user()->nots_mail_settings_state_specific = $temp;
    Auth::user()->save();

    return back();
  }
}
