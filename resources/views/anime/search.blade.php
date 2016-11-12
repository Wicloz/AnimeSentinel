@extends('anime.browse')
@section('title', 'Browse Anime')

@section('form-left')
  <div class="content-generic content-dark">
    <div class="form-group">
      <label for="search-source">Search Source:</label>
      <select class="form-control" id="search-source" name="source">
        <option value="mal" {{ request('source') === 'mal' ? 'selected' : '' }}>MyAnimeList</option>
        <option value="as" {{ request('source') === 'as' ? 'selected' : '' }}>AnimeSentinel</option>
        <option value="" {{ request('source') !== 'mal' && request('source') !== 'as' ? 'selected' : '' }}>Hybrid</option>
      </select>
    </div>
  </div>
@endsection
