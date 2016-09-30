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

      <div class="form-group" id="search-types">
        <label for="search-types">Types:</label>

        <input type="hidden" name="type-tv" value="off">
        <input type="hidden" name="type-ova" value="off">
        <input type="hidden" name="type-ona" value="off">
        <input type="hidden" name="type-movie" value="off">
        <input type="hidden" name="type-special" value="off">

        <div class="checkbox">
          <label>
            <input type="checkbox" name="type-tv" {{ request('type-tv') !== 'off' ? 'checked' : '' }}>
            Tv
          </label>
        </div>
        <div class="checkbox">
          <label>
            <input type="checkbox" name="type-ova" {{ request('type-ova') !== 'off' ? 'checked' : '' }}>
            Ova
          </label>
        </div>
        <div class="checkbox">
          <label>
            <input type="checkbox" name="type-ona" {{ request('type-ona') !== 'off' ? 'checked' : '' }}>
            Ona
          </label>
        </div>
        <div class="checkbox">
          <label>
            <input type="checkbox" name="type-movie" {{ request('type-movie') !== 'off' ? 'checked' : '' }}>
            Movie
          </label>
        </div>
        <div class="checkbox">
          <label>
            <input type="checkbox" name="type-special" {{ request('type-special') !== 'off' ? 'checked' : '' }}>
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
