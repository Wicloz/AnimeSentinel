@extends('layouts.app')
@section('title', 'Search Anime')

@section('content-top')
  <p>TODO: Search Bar</p>
@endsection

@section('content-center')
  <div class="content-section">
    <div class="section-heading">Search Results</div>
    <div class="section-body">
      @foreach($results as $result)

      @endforeach
    </div>
  </div>
@endsection
