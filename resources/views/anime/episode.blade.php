@extends('layouts.app')
@section('title', "$show->title - Episode $number")

@section('content-center')
  <div class="content-header">{{ $show->title }} - Episode {{ $number }}</div>
  <div class="content-generic">
    <ul>
      @foreach($videos as $video)
        <li>
          <a href="{{ $video->stream_url }}">Video with {{ $video->resolution }} resolution.</a>
        </li>
      @endforeach
    </ul>
    <div class="content-close"></div>
  </div>

  <div class="content-header">
    <a target="_blank" href="{{ $show->mal_url }}">View on MyAnimeList</a>
  </div>
  <div class="content-generic flowfix">
    <div class="mal-widget">
      <iframe src="{{ $show->mal_url }}"></iframe>
    </div>
    <div class="content-close"></div>
  </div>
@endsection
