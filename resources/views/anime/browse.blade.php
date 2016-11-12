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
    @yield('form-left')

    <div class="content-generic content-dark">
      <div class="form-group" id="search-types">
        <label for="search-types">Types:</label>
        @foreach ($checkboxes['types'] as $type)
          <input type="hidden" name="type_{{ $type }}" value="off">
        @endforeach
        @foreach ($checkboxes['types'] as $type)
          <div class="checkbox">
            <label>
              <input type="checkbox" name="type_{{ $type }}" {{ request('type_'.$type) !== 'off' ? 'checked' : '' }}>
              {{ ucwords($type) }}
            </label>
          </div>
        @endforeach
      </div>
    </div>

    <div class="content-generic content-dark">
      <div class="form-group" id="search-genres">
        <label for="search-genres">Genres:</label>
        @foreach ($checkboxes['genres'] as $genre)
          <input type="hidden" name="genre_{{ $genre }}" value="off">
        @endforeach
        @foreach ($checkboxes['genres'] as $genre)
          <div class="checkbox">
            <label>
              <input type="checkbox" name="genre_{{ str_replace(' ', '_', $genre) }}" {{ request('genre_'.str_replace(' ', '_', $genre)) !== 'off' ? 'checked' : '' }}>
              @if ($genre === 'Unknown')
                Unknown (NOTE: All results from MAL have an unknown genre)
              @else
                {{ $genre }}
              @endif
            </label>
          </div>
        @endforeach
      </div>
    </div>

    <div class="content-generic content-dark">
      <div class="form-group" id="search-ratings">
        <label for="search-ratings">Ratings:</label>
        @foreach ($checkboxes['ratings'] as $rating)
          <input type="hidden" name="rating_{{ $rating }}" value="off">
        @endforeach
        @foreach ($checkboxes['ratings'] as $rating)
          <div class="checkbox">
            <label>
              <input type="checkbox" name="rating_{{ $rating }}" {{ request('rating_'.$rating) !== 'off' ? 'checked' : '' }}>
              {{ $rating }}
            </label>
          </div>
        @endforeach
      </div>
    </div>

    <div class="content-generic content-dark">
      <button type="submit" class="btn btn-primary">Search</button>
      <button type="reset" class="btn btn-default">Reset</button>
    </div>
  </form>
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
    <a href="{{ '?page='.(request()->page + 1) }}" class="arrow-right {{ request()->nextPage ? '' : 'arrow-hide' }}" onclick="
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
    @if ($display === 'table')
      <div class="content-generic">
        @include('components.anime.table', [
          'shows' => $results->pluck('show'),
          'videos' => $results->pluck('video'),
          'columns' => [
            'thumbnail',
            'title',
            'description',
            'type',
            'genres',
            'rating',
            'season',
            'episode_amount',
            'episode_duration',
            'videos',
            'broadcasts',
          ],
        ])
        <div class="content-close"></div>
      </div>
    @else
      @foreach($results as $result)
        @include('components.listitems.'.$display, $result)
      @endforeach
    @endif
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
    <a href="{{ '?page='.(request()->page + 1) }}" class="arrow-right {{ request()->nextPage ? '' : 'arrow-hide' }}" onclick="
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
          <option value="table" {{ $display === 'table' ? 'selected' : '' }}>Table</option>
        </select>
      </div>

      <button type="submit" class="btn btn-primary">Set</button>
    </form>
  </div>

  @yield('form-right')
@endsection
