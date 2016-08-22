@extends('layouts.app')
@section('title', 'Search Anime')

@section('content-top')
  <div class="container-fluid searchbar-top">
    <form method="GET">
      <div class="form-group">
        <label for="search-mode">Search Source</label>
        <select class="form-control" id="search-mode" name="mode">
          <option value="mal" {{ request('mode') === 'mal' ? 'selected' : '' }}>MyAnimeList</option>
          <option value="as" {{ request('mode') === 'as' ? 'selected' : '' }}>AnimeSentinel</option>
          <option value="hybrid" {{ request('mode') !== 'mal' && request('mode') !== 'as' ? 'selected' : '' }}>Both</option>
        </select>
      </div>
      <div class="form-group searchfield">
        <label class="sr-only" for="search-query">Search Query</label>
        <div class="input-group">
          <div class="input-group-addon loop">&#128269;</div>
          <input type="text" class="form-control" id="search-query" name="q" value="{{ request('q') }}" placeholder="Search ...">
        </div>
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
