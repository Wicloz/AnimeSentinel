<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;

use App\Streamer;

class StreamersController extends Controller
{
  /**
   * Show a list of all registered streamers.
   *
   * @return \Illuminate\Http\Response
   */
  public function list() {
    return view('streamers.list', [
      'streamers' => Streamer::all()
    ]);
  }

  /**
   * Show the details page for a streamer.
   *
   * @return \Illuminate\Http\Response
   */
  public function details(Streamer $streamer) {
    return view('streamers.details', [
      'streamer' => $streamer
    ]);
  }
}
