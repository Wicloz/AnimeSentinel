@extends('layouts.app')
@section('title', "$show->title - Episode $number")

@section('content-left')
  <img class="img-thumbnail details-thumbnail" src="{{ url('/media/thumbnails/'.$show->thumbnail_id) }}" alt="{{ $show->title }} - Thumbnail">
@endsection

@section('content-center')
  <div class="content-header">{{ $show->title }} - Episode {{ $number }}</div>
  <div class="content-generic">
    @foreach($resolutions as $resolution)
      <ul class="list-group">
        <li class="list-group-item">
          <h2>{{ $resolution }}</h2>
        </li>
        <li class="list-group-item item-blockholder">
          <div class="row">
            @foreach($videos as $video)
              @if($video->resolution === $resolution)
                <div class="col-sm-4">
                  <a href="{{ $video->stream_url }}">
                    <div class="episode-block">
                      <p>Original Streamer: {{ $video->streamer->name }}</p>
                      @if(str_ends_with($video->link_video, '.html'))
                        <p class="embed-no">HTML5 Player: No</p>
                      @else
                        <p class="embed-yes">HTML5 Player: Yes</p>
                      @endif
                    </div>
                  </a>
                </div>
              @endif
            @endforeach
          </div>
        </li>
      </ul>
    @endforeach
    <div class="content-close"></div>
  </div>

  <div class="content-header">
    <a target="_blank" href="{{ $show->mal_url }}">View on MyAnimeList</a>
  </div>
  <div class="content-generic flowfix">
    <div class="mal-widget">
      <iframe src="{{ $show->mal_url }}" scrolling="no"></iframe>
    </div>
    <div class="content-close"></div>
  </div>
@endsection
