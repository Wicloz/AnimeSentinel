@extends('anime.browse')
@section('title', 'Recent Anime')

@section('form-left')
  <div class="form-group" id="search-streamers">
    <label for="search-streamers">Streamers:</label>

    <input type="hidden" name="streamer_animeshow" value="off">
    <input type="hidden" name="streamer_kissanime" value="off">

    <div class="checkbox">
      <label>
        <input type="checkbox" name="streamer_animeshow" {{ request('streamer_animeshow') !== 'off' ? 'checked' : '' }}>
        AnimeShow.tv
      </label>
    </div>
    <div class="checkbox">
      <label>
        <input type="checkbox" name="streamer_kissanime" {{ request('streamer_kissanime') !== 'off' ? 'checked' : '' }}>
        KissAnime
      </label>
    </div>
  </div>
@endsection

@section('form-right')
  <div class="content-generic content-dark">
    <form action="{{ fullUrl('/anime/recent/setdistinct') }}" method="POST">
      {{ csrf_field() }}

      <div class="form-group" id="option-distinct">
        <label for="option-distinct">Show One Entry Per:</label>

        <div class="checkbox">
          <label>
            <input type="checkbox" name="distinct_show_id" {{ in_array('show_id', $request->distincts) ? 'checked' : '' }}>
            Show
          </label>
        </div>
        <div class="checkbox">
          <label>
            <input type="checkbox" name="distinct_translation_type" {{ in_array('translation_type', $request->distincts) ? 'checked' : '' }}>
            Translation Type
          </label>
        </div>
        <div class="checkbox">
          <label>
            <input type="checkbox" name="distinct_episode_num" {{ in_array('episode_num', $request->distincts) ? 'checked' : '' }}>
            Episode
          </label>
        </div>
        <div class="checkbox">
          <label>
            <input type="checkbox" name="distinct_streamer_id" {{ in_array('streamer_id', $request->distincts) ? 'checked' : '' }}>
            Streamer
          </label>
        </div>
        <div class="checkbox">
          <label>
            <input type="checkbox" name="distinct_mirror" {{ in_array('mirror', $request->distincts) ? 'checked' : '' }}>
            Mirror
          </label>
        </div>
      </div>

      <button type="submit" class="btn btn-primary">Set</button>
    </form>
  </div>
@endsection
