@extends('anime.browse')
@section('title', 'Recent Anime')

@section('form-left')
  <div class="form-group" id="search-streamers">
    <label for="search-streamers">Streamers:</label>

    <input type="hidden" name="streamer_animeshow" value="off">
    <input type="hidden" name="streamer_kissanime" value="off">

    <div class="radio">
      <label>
        <input type="radio" name="streamer_animeshow" {{ request('streamer_animeshow') !== 'off' ? 'checked' : '' }}>
        AnimeShow.tv
      </label>
    </div>
    <div class="radio">
      <label>
        <input type="radio" name="streamer_kissanime" {{ request('streamer_kissanime') !== 'off' ? 'checked' : '' }}>
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
        <div class="radio">
          <label>
            <input type="radio" name="distinct" value="show_id" {{ $distinct === 'show_id' ? 'checked' : '' }}>
            Show
          </label>
        </div>
        <div class="radio">
          <label>
            <input type="radio" name="distinct" value="translation_type" {{ $distinct === 'translation_type' ? 'checked' : '' }}>
            Translation Type
          </label>
        </div>
        <div class="radio">
          <label>
            <input type="radio" name="distinct" value="episode_num" {{ $distinct === 'episode_num' ? 'checked' : '' }}>
            Episode
          </label>
        </div>
        <div class="radio">
          <label>
            <input type="radio" name="distinct" value="streamer_id" {{ $distinct === 'streamer_id' ? 'checked' : '' }}>
            Streamer
          </label>
        </div>
        <div class="radio">
          <label>
            <input type="radio" name="distinct" value="mirror" {{ $distinct === 'mirror' ? 'checked' : '' }}>
            Mirror
          </label>
        </div>
      </div>

      <button type="submit" class="btn btn-primary">Set</button>
    </form>
  </div>
@endsection
