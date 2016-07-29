@extends('layouts.app')
@section('title', 'Search Anime')

@section('content-top')
  <div class="container-fluid">
    <form class="searchbar-top" method="GET">
      <div class="form-group">
        <input type="text" name="q" class="form-control" value="{{ request('q') }}" placeholder="Search ..."></input>
        <button type="submit" class="btn btn-primary pull-right">Search</button>
      </div>
      <p>
        Note: If your search times out, retry until it doesn't. This is because certain data from MAL has to be stored the first time an anime is shown here, which can take a while.
      </p>
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
          'syn_unique' => $result->id,
        ])
      @endif
    @endforeach
  @endif
@endsection
