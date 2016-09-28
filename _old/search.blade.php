@extends('layouts.app')
@section('title', 'Search Anime')

@section('content-top')
  <div class="container-fluid searchbar-top">
    <form method="GET">
      <div class="form-group group-source">
        <label for="search-source">Search Source:</label>
        <select class="form-control" id="search-source" name="source">
          <option value="mal" {{ request('source') === 'mal' ? 'selected' : '' }}>MyAnimeList</option>
          <option value="as" {{ request('source') === 'as' ? 'selected' : '' }}>AnimeSentinel</option>
          <option value="" {{ request('source') !== 'mal' && request('source') !== 'as' ? 'selected' : '' }}>Both</option>
        </select>
      </div>
      <div class="form-group group-search {{ $errors->has('q') ? 'has-error' : '' }}">
        <label for="search-query">Search Query:</label>
        <div class="input-group">
          <div class="input-group-addon loop">&#128269;</div>
          <input type="text" class="form-control" id="search-query" name="q" value="{{ request('q') }}" placeholder="Search ..." maxlength="255" autofocus>
        </div>
        @if ($errors->has('q'))
          <span class="help-block">
            <strong>{{ $errors->first('q') }}</strong>
          </span>
        @endif
      </div>
      <button type="submit" class="btn btn-primary pull-right">Search</button>
    </form>
  </div>
@endsection

@section('content-center')
  @if (count($results) === 0)
    <div class="content-header">No Results</div>
  @else
    <div class="content-header">Search Results</div>
    @foreach($results as $result)
      @if(empty($result->mal))
        @include('components.synopsis', [
          'syn_mal' => false,
          'syn_show' => $result,
          'syn_unique' => $result->id,
        ])
      @else
        @include('components.synopsis', [
          'syn_mal' => true,
          'syn_show' => $result,
          'syn_unique' => $result->mal_id,
        ])
      @endif
    @endforeach
  @endif
@endsection
