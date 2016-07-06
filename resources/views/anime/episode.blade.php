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
@endsection
