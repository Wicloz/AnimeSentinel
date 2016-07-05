@extends('layouts.app')
@section('title', 'Search Anime')

@section('content-top')
  TODO: Search Bar
@endsection

@section('content-center')
  <div class="content-section">
    <div class="section-heading">Search Results</div>
    <div class="section-body">
      <ul>
        @foreach($results as $result)
          <li>
            <a href="{{ url("/anime/$result->id") }}">{{ $result->name }}</a>
            <ul>
              <li>{{ $result->alts }}</li>
              <li>{{ $result->description }}</li>
            </ul>
          </li>
          <br>
        @endforeach
      </ul>
    </div>
  </div>
@endsection
