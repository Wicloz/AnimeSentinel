<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;

use App\AnimeSentinel\Actions\ShowManager;
use App\AnimeSentinel\Actions\FindVideos;
use App\Video;
use App\Show;

class PostController extends Controller
{
  /**
   * Attempt to add the requested show to the database.
   *
   * @return \Illuminate\Http\Response
   */
  public function showAdd(Request $request) {
    $this->validate($request, [
      'mal_id' => ['required', 'integer']
    ]);

    $show = ShowManager::addShowWithMalId($request->mal_id);

    if ($request->gotodetails) {
      return redirect($show->details_url);
    } else {
      return back();
    }
  }

  /**
   * Refresh the cache for the requested show
   *
   * @return \Illuminate\Http\Response
   */
  public function showUpdate(Request $request) {
    $this->validate($request, [
      'show_id' => ['required', 'integer']
    ]);

    $show = Show::find($request->show_id);
    if (isset($show)) {
      ShowManager::updateShowCache($show->id);
    } else {
      flash_error('The requested anime could not be found.');
    }

    return back();
  }

  /**
   * Refresh all videos for the requested show
   *
   * @return \Illuminate\Http\Response
   */
  public function showRefreshVideos(Request $request) {
    $this->validate($request, [
      'show_id' => ['required', 'integer']
    ]);

    $show = Show::find($request->show_id);
    if (isset($show)) {
      queueJob(new \App\Jobs\AnimeFindVideos($show), 'high');
    } else {
      flash_error('The requested anime could not be found.');
    }

    return back();
  }

  /**
   * Reprocess the episode that the given video belongs to.
   *
   * @return \Illuminate\Http\Response
   */
  public function animeReprocessEpisodes(Request $request) {
    $this->validate($request, [
      'video_id' => ['required', 'integer']
    ]);

    $video = Video::find($request->video_id);

    if (isset($video)) {
      FindVideos::reprocessEpsiodes($video->show, [$video->translation_type], $video->episode_num);
      return redirect($video->episode_url);
    }

    else {
      flash_error('The requested episode could not be found.');
      return back();
    }
  }
}
