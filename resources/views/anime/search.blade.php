@extends('layouts.app')
@section('title', 'Search Anime')

@section('content-top')
  <div class="container-fluid">
    <form class="searchbar-top" method="GET">
      <div class="form-group">
        <input type="text" name="q" class="form-control" value="{{ request('q') }}" placeholder="Search ..."></input>
        <button type="submit" class="btn btn-primary pull-right">Search</button>
      </div>
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
