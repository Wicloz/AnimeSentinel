@extends('anime.browse')
@section('title', 'Recent Anime')

@section('form-left')
  <div class="content-generic content-dark">
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
  </div>
@endsection

@section('form-right')
  <div class="content-generic content-dark">
    <form action="{{ fullUrl('/anime/recent/setttype') }}" method="POST">
      {{ csrf_field() }}

      <div class="form-group" id="option-ttypes">
        <label for="option-ttypes">Translation Types:</label>
        <div class="checkbox">
          <label>
            <input type="checkbox" name="ttype_sub" {{ request()->ttypes->contains('sub') ? 'checked' : '' }}>
            Subbed
          </label>
        </div>
        <div class="checkbox">
          <label>
            <input type="checkbox" name="ttype_dub" {{ request()->ttypes->contains('dub') ? 'checked' : '' }}>
            Dubbed
          </label>
        </div>
      </div>

      <button type="submit" class="btn btn-primary">Set</button>
    </form>
  </div>

  <div class="content-generic content-dark">
    <form action="{{ fullUrl('/anime/recent/setdistinct') }}" method="POST">
      {{ csrf_field() }}

      <div class="form-group" id="option-distinct">
        <label for="option-distinct">Amount of Episodes Shown:</label>
        <div class="radio">
          <label>
            <input type="radio" name="distinct" value="show_id" {{ request()->distincts->keys()->last() === 'show_id' ? 'checked' : '' }}>
            Show the latest episode (subbed or dubbed) for each anime.
          </label>
        </div>
        <div class="radio">
          <label>
            <input type="radio" name="distinct" value="translation_type" {{ request()->distincts->keys()->last() === 'translation_type' ? 'checked' : '' }}>
            Show the latest subbed and latest dubbed episode for each anime.
          </label>
        </div>
        <div class="radio">
          <label>
            <input type="radio" name="distinct" value="episode_num" {{ request()->distincts->keys()->last() === 'episode_num' ? 'checked' : '' }}>
            Show every episode for each anime from the first streaming site.
          </label>
        </div>
        <div class="radio">
          <label>
            <input type="radio" name="distinct" value="streamer_id" {{ request()->distincts->keys()->last() === 'streamer_id' ? 'checked' : '' }}>
            Show every episode for each anime from all streaming sites.
          </label>
        </div>
      </div>

      <button type="submit" class="btn btn-primary">Set</button>
    </form>
  </div>
@endsection
