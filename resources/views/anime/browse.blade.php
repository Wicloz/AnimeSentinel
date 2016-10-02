@extends('layouts.app')

@section('content-top')
  <div class="container-fluid searchbar-top">
    <form method="GET" id="search-form">
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
  <div class="content-header header-center">
    <a href="{{ '?page='.(request()->page - 1) }}" class="arrow-left {{ request()->page > 1 ? '' : 'arrow-hide' }}" onclick="
      event.preventDefault();
      var form = document.getElementById('search-form');
      var input = document.createElement('input');
      input.type = 'hidden';
      input.name = 'page';
      input.value = {{ request()->page - 1 }};
      form.appendChild(input);
      form.submit();
    ">&#8666;</a>
    Results - Page {{ request()->page }}
    <a href="{{ '?page='.(request()->page + 1) }}" class="arrow-right {{ count($results) >= 50 ? '' : 'arrow-hide' }}" onclick="
      event.preventDefault();
      var form = document.getElementById('search-form');
      var input = document.createElement('input');
      input.type = 'hidden';
      input.name = 'page';
      input.value = {{ request()->page + 1 }};
      form.appendChild(input);
      form.submit();
    ">&#8667;</a>
  </div>

  @if (count($results) === 0)
    <div class="content-header">No Results</div>
  @else
    @foreach($results as $result)
      @include('components.listitems.'.$display, $result)
    @endforeach
  @endif

  <div class="content-header header-center">
    <a href="{{ '?page='.(request()->page - 1) }}" class="arrow-left {{ request()->page > 1 ? '' : 'arrow-hide' }}" onclick="
      event.preventDefault();
      var form = document.getElementById('search-form');
      var input = document.createElement('input');
      input.type = 'hidden';
      input.name = 'page';
      input.value = {{ request()->page - 1 }};
      form.appendChild(input);
      form.submit();
    ">&#8666;</a>
    Results - Page {{ request()->page }}
    <a href="{{ '?page='.(request()->page + 1) }}" class="arrow-right {{ count($results) >= 50 ? '' : 'arrow-hide' }}" onclick="
      event.preventDefault();
      var form = document.getElementById('search-form');
      var input = document.createElement('input');
      input.type = 'hidden';
      input.name = 'page';
      input.value = {{ request()->page + 1 }};
      form.appendChild(input);
      form.submit();
    ">&#8667;</a>
  </div>
@endsection

@section('content-right')
  <div class="content-generic content-dark">
    <form action="{{ fullUrl('/anime/setdisplay') }}" method="POST">
      {{ csrf_field() }}
      <input type="hidden" name="page" value="{{ $mode }}"></input>

      <div class="form-group">
        <label for="option-display">List Type:</label>
        <select class="form-control" id="option-display" name="display">
          <option value="smallrow" {{ $display === 'smallrow' ? 'selected' : '' }}>Small Rows</option>
          <option value="bigrow" {{ $display === 'bigrow' ? 'selected' : '' }}>Big Rows</option>
        </select>
      </div>

      <button type="submit" class="btn btn-primary">Set</button>
    </form>
  </div>

  @yield('form-right')
@endsection
