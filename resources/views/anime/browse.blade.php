@extends('layouts.app')

@section('content-top')
  <div class="container-fluid searchbar-top">
    <form method="GET">
      <div class="form-group group-search {{ $errors->has('q') ? 'has-error' : '' }}">
        <label for="search-query">Search Query:</label>
        <div class="input-group">
          <div class="input-group-addon loop-icon">&#128269;</div>
          <input type="text" class="form-control" id="search-query" name="q" value="{{ $errors->has('q') ? old('q') : request('q') }}" placeholder="Search ..." maxlength="255" autofocus>
        </div>
        @if ($errors->has('q'))
          <span class="help-block">
            <strong>{{ $errors->first('q') }}</strong>
          </span>
        @endif
      </div>
      <button type="submit" class="btn btn-primary pull-right">Search</button>
  </div>
@endsection

@section('content-left')
  <div class="content-generic content-dark">
      @yield('form-left')

      <div class="form-group" id="search-streamers">
        <label for="search-streamers">Streamers:</label>

        <input type="hidden" name="streamer_tv" value="off">
        <input type="hidden" name="streamer_ova" value="off">

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

      <div class="form-group" id="search-types">
        <label for="search-types">Types:</label>

        <input type="hidden" name="type_tv" value="off">
        <input type="hidden" name="type_ova" value="off">
        <input type="hidden" name="type_ona" value="off">
        <input type="hidden" name="type_movie" value="off">
        <input type="hidden" name="type_special" value="off">

        <div class="checkbox">
          <label>
            <input type="checkbox" name="type_tv" {{ request('type_tv') !== 'off' ? 'checked' : '' }}>
            Tv
          </label>
        </div>
        <div class="checkbox">
          <label>
            <input type="checkbox" name="type_ova" {{ request('type_ova') !== 'off' ? 'checked' : '' }}>
            Ova
          </label>
        </div>
        <div class="checkbox">
          <label>
            <input type="checkbox" name="type_ona" {{ request('type_ona') !== 'off' ? 'checked' : '' }}>
            Ona
          </label>
        </div>
        <div class="checkbox">
          <label>
            <input type="checkbox" name="type_movie" {{ request('type_movie') !== 'off' ? 'checked' : '' }}>
            Movie
          </label>
        </div>
        <div class="checkbox">
          <label>
            <input type="checkbox" name="type_special" {{ request('type_special') !== 'off' ? 'checked' : '' }}>
            Special
          </label>
        </div>
      </div>

      <button type="submit" class="btn btn-primary">Search</button>
      <button type="reset" class="btn btn-default">Reset</button>
    </form>
  </div>
@endsection

@section('content-center')
  @if (count($results) === 0)
    <div class="content-header">No Results</div>
  @else
    <div class="content-header">Results</div>
    @foreach($results as $result)
      @include('components.listitems.'.$display, $result)
    @endforeach
  @endif
@endsection

@section('content-right')
  @yield('content-right')
@endsection
