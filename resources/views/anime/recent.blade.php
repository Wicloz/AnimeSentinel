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
