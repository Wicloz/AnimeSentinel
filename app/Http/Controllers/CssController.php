<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;

class CssController extends Controller
{
  /**
   * Create a new controller instance.
   *
   * @return void
   */
  public function __construct() {
    $this->middleware('auth', ['only' => ['showUserSettings', 'setUserSettings']]);
  }

  /**
   * Show a settings page for the user defined CSS configuration.
   *
   * @return \Illuminate\Http\Response
   */
  public function showUserSettings() {
    return view('users.settings.css');
  }

  /**
   * Update the user defined CSS configuration.
   *
   * @return \Illuminate\Http\Response
   */
  public function setUserSettings(Request $request) {

  }
}
