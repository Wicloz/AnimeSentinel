@extends('layouts.app')
@section('title', 'Search Anime')

@section('content-top')
  <p>TODO: Search Bar</p>
@endsection

@section('content-center')
  <div class="content-header">Search Results</div>
  @foreach($results as $result)

  @endforeach
@endsection
