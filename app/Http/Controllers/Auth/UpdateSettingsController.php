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
    $checkboxes = ['nots_mail_state', 'auto_watching_state'];

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
      Auth::user()->updateMalCache(true);
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
    $this->validate($request, [
      'setting_watching' => ['required'],
      'setting_completed' => ['required'],
      'setting_onhold' => ['required'],
      'setting_dropped' => ['required'],
      'setting_plantowatch' => ['required'],
    ]);

    Auth::user()->nots_mail_settings = [
      'watching' => $request->setting_watching,
      'completed' => $request->setting_completed,
      'onhold' => $request->setting_onhold,
      'dropped' => $request->setting_dropped,
      'plantowatch' => $request->setting_plantowatch,
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
    foreach ($request->all() as $key => $value) {
      if (str_starts_with($key, 'setting_')) {
        $key = (int) str_replace('setting_', '', $key);
        if ($value === 'null') {
          Auth::user()->malFields()->where('mal_id', $key)->update(['nots_mail_setting' => null]);
        } else {
          Auth::user()->malFields()->where('mal_id', $key)->update(['nots_mail_setting' => $value]);
        }
      }
    }

    return back();
  }

  /**
   * Update the setting for this user's overview page.
   *
   * @return \Illuminate\Http\Response
   */
  public function overview(Request $request) {
    $this->validate($request, [
      'option_cutoff' => ['required', 'integer', 'between:1,max'],
    ]);

    $states = collect(['watching', 'completed', 'onhold', 'dropped', 'plantowatch']);
    $states = $states->filter(function ($value, $key) use ($request) {
      return $request->{'state_'.$value} === 'on';
    });

    Auth::user()->viewsettings_overview = [
      'states' => $states->values(),
      'thumbnails' => $request->option_thumbnails === 'on',
      'cutoff' => $request->option_cutoff,
    ];
    Auth::user()->save();

    return back();
  }
}
