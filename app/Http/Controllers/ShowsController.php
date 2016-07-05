<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;

class ShowsController extends Controller
{
  /**
   * Show the search page for all shows.
   *
   * @return \Illuminate\Http\Response
   */
  public function search() {
      return view('shows.search');
  }

  /**
   * Show the details page for a show.
   *
   * @return \Illuminate\Http\Response
   */
  public function details() {
      return view('shows.details');
  }
}
