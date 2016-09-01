<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;

class PagesController extends Controller
{
  /**
   * Show the about page.
   *
   * @return \Illuminate\Http\Response
   */
  public function about() {
    return view('information.about');
  }

  /**
   * Show the page with MAL infromation.
   *
   * @return \Illuminate\Http\Response
   */
  public function about_mal() {
    return view('information.mal');
  }

  /**
   * Show the news page.
   *
   * @return \Illuminate\Http\Response
   */
  public function news() {
    return view('information.news');
  }
}
