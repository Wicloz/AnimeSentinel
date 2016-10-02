@extends('anime.browse')
@section('title', 'Recent Anime')

@section('form-left')
  <div class="form-group" id="search-streamers">
    <label for="search-streamers">Streamers:</label>
    @foreach ($checkboxes['streamers'] as $streamer)
      <input type="hidden" name="streamer_{{ $streamer }}" value="off">
    @endforeach
    @foreach ($checkboxes['streamers'] as $streamer)
      <div class="checkbox">
        <label>
          <input type="checkbox" name="streamer_{{ $streamer }}" {{ request('streamer_'.$streamer) !== 'off' ? 'checked' : '' }}>
          {{ ucwords($streamer) }}
        </label>
      </div>
    @endforeach
  </div>

  <div class="form-group" id="search-ttypes">
    <label for="search-ttypes">Translation Types:</label>
    @foreach ($checkboxes['ttypes'] as $ttype)
      <input type="hidden" name="ttype_{{ $ttype }}" value="off">
    @endforeach
    @foreach ($checkboxes['ttypes'] as $ttype)
      <div class="checkbox">
        <label>
          <input type="checkbox" name="ttype_{{ $ttype }}" {{ request('ttype_'.$ttype) !== 'off' ? 'checked' : '' }}>
          {{ $ttype === 'sub' ? 'Subbed' : '' }}{{ $ttype === 'dub' ? 'Dubbed' : ''}}
        </label>
      </div>
    @endforeach
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
