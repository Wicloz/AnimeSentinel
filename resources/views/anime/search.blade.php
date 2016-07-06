@extends('layouts.app')
@section('title', 'Search Anime')

@section('content-top')
  <p>TODO: Search Bar</p>
@endsection

@section('content-center')
  <div class="content-header">Search Results</div>
  @foreach($results as $result)
    <div class="synopsis-panel">
      <div class="row">
        <div class="col-sm-2">
          <img class="img-thumbnail synopsis-thumbnail" src="{{ url('/media/thumbnails/'.$result->id) }}" alt="{{ $result->title }} thumbnail">
        </div>
        <div class="col-sm-10">
          <div class="synopsis-title">{{ $result->title }}</div>
          <div class="synopsis-details">
            {{ $result->description }}
          </div>
          <div class="synopsis-episodes">
            <div class="row">
              <div class="col-sm-6">
                Latest Subbed Episode: {{ $result->latest_sub !== -1 ? '<a href="'.url("/anime/$result->id/sub/episode-$result->latest_sub").'">'.$result->latest_sub.'</a>' : "Not Available" }}
              </div>
              <div class="col-sm-6">
                Latest Dubbed Episode: {{ $result->latest_dub !== -1 ? '<a href="'.url("/anime/$result->id/dub/episode-$result->latest_dub").'">'.$result->latest_dub.'</a>' : "Not Available" }}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  @endforeach
@endsection
