@extends('layouts.app')
@section('title', 'Search Anime')

@section('content-top')
  <div class="container-fluid">
    <div class="row">
      <form class="searchbar-top" method="POST">
        {{ csrf_field() }}
        <div class="form-group">
          <input type="text" name="query" class="form-control" value="{{ request('query') }}" placeholder="Search ..."></input>
          <button type="submit" class="btn btn-primary pull-right">Search</button>
        </div>
      </form>
    </div>
  </div>
@endsection

@section('content-center')
  @if (count($results) === 0)
    <div class="content-header">No Results</div>
  @else
    <div class="content-header">Search Results</div>
    <div class="content-generic">
      @foreach($results as $result)
        <p>
          {{ $result->title }}
        </p>
      @endforeach
      <div class="content-close"></div>
    </div>
  @endif
@endsection
