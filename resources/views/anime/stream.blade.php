@extends('layouts.app')
@section('title', $video->show->title.' - '.($video->translation_type === 'sub' ? 'Subbed' : '').($video->translation_type === 'dub' ? 'Dubbed' : '').' - Episode '.$video->episode_num.' - Watch Online in '.$video->resolution)

@section('content-left')
  <a href="{{ $video->show->details_url }}">
    <img class="img-thumbnail details-thumbnail" src="{{ url('/media/thumbnails/'.$video->show->thumbnail_id) }}" alt="{{ $video->show->title }} - Thumbnail">
  </a>
  @include('components.animedetails', ['details' => $video->show, 'link' => true])
  <div class="content-header">
    <a target="_blank" href="{{ $video->show->mal_url }}">View on MyAnimeList</a>
  </div>
@endsection

@section('content-center')
  <div class="content-header header-center">
    <a href="{{ $video->pre_episode_url }}" class="arrow-left {{ empty($video->pre_episode_url) ? 'arrow-hide' : '' }}">&#8666;</a>
    <a href="{{ $video->show->details_url }}">{{ $video->show->title }}</a>
    - {{ $video->translation_type === 'sub' ? 'Subbed' : '' }}{{ $video->translation_type === 'dub' ? 'Dubbed' : '' }} -
    <a href="{{ $video->episode_url }}">Episode {{ $video->episode_num }}</a>
    <!-- TODO: select episodes with dropdown -->
    <a href="{{ $video->next_episode_url }}" class="arrow-right {{ empty($video->next_episode_url) ? 'arrow-hide' : '' }}">&#8667;</a>
  </div>

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

  @include('components.malwidget', ['mal_url' => $video->show->mal_url])

  <div class="content-header">Comments</div>
  <!-- TODO: Disqus integration -->
@endsection
