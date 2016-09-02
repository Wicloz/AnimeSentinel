<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;

class CssController extends Controller
{
  /**
   * Show a settings page for the user defined CSS configuration.
   *
   * @return \Illuminate\Http\Response
   */
  public function userSettings() {
    return view('users.settings.css');
  }
}
