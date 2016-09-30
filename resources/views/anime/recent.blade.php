@extends('anime.browse')
@section('title', 'Recent Anime')

@section('form-left')
  <div class="form-group" id="search-streamers">
    <label for="search-streamers">From Streamers:</label>

    <input type="hidden" name="streamer-tv" value="off">
    <input type="hidden" name="streamer-ova" value="off">

    <div class="checkbox">
      <label>
        <input type="checkbox" name="streamer-animeshow" {{ request('streamer-animeshow') !== 'off' ? 'checked' : '' }}>
        AnimeShow.tv
      </label>
    </div>
    <div class="checkbox">
      <label>
        <input type="checkbox" name="streamer-kissanime" {{ request('streamer-kissanime') !== 'off' ? 'checked' : '' }}>
        KissAnime
      </label>
    </div>
  </div>
@endsection
