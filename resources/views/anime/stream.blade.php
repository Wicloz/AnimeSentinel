@extends('layouts.app')
@section('title', $video->show->title." - Episode $video->episode_num Watch Online in $video->resolution")

@section('content-left')
  <img class="img-thumbnail details-thumbnail" src="{{ url('/media/thumbnails/'.$video->show->thumbnail_id) }}" alt="{{ $video->show->title }} - Thumbnail">

  <div class="content-header">
    <a target="_blank" href="{{ $video->show->mal_url }}">View on MyAnimeList</a>
  </div>
  <div class="content-generic flowfix">
    <div class="mal-widget">
      <iframe src="{{ $video->show->mal_url }}" scrolling="no"></iframe>
    </div>
    <div class="content-close"></div>
  </div>
@endsection

@section('content-center')
  <div class="content-header">{{ $video->show->title }} - Episode {{ $video->episode_num }}</div>
  <div class="content-generic">
    <div class="streamplayer">
      @if(str_ends_with($video->link_video, '.mp4'))
        <video class="streamplayer-video" controls>
          <source src="{{ $video->link_video }}" type="video/mp4">
          Your browser does not support the video tag.
        </video>
      @elseif(str_ends_with($video->link_video, '.html'))
        <iframe class="streamplayer-video streamplayer-embed" src="{{ $video->link_video }}" scrolling="no"></iframe>
      @endif
      <div class="content-close"></div>
    </div>
  </div>

  <div class="content-header">Comments</div>
  <!-- TODO: Disqus integration -->
@endsection
