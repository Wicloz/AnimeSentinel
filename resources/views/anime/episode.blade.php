@extends('layouts.app')
@section('title', $show->title.' - '.($episode->translation_type === 'sub' ? 'Subbed' : '').($episode->translation_type === 'dub' ? 'Dubbed' : '').' - Episode '.$episode->episode_num)

@section('content-left')
  <a href="{{ $show->details_url }}">
    <img class="img-thumbnail details-thumbnail" src="{{ url('/media/thumbnails/'.$show->thumbnail_id) }}" alt="{{ $show->title }} - Thumbnail">
  </a>
  @include('components.animedetails', ['details' => $show, 'link' => true])
  <div class="content-header">
    <a target="_blank" href="{{ $show->mal_url }}">View on MyAnimeList</a>
  </div>
@endsection

@section('content-center')
  <div class="content-header header-center">
    <a href="{{ $episode->pre_episode_url }}" class="arrow-left {{ empty($episode->pre_episode_url) ? 'arrow-hide' : '' }}">&#8666;</a>
    <a href="{{ $show->details_url }}">{{ $show->title }}</a>
    - {{ $episode->translation_type === 'sub' ? 'Subbed' : '' }}{{ $episode->translation_type === 'dub' ? 'Dubbed' : '' }} -
    Episode {{ $episode->episode_num }}
    <!-- TODO: select episodes with dropdown -->
    <a href="{{ $episode->next_episode_url }}" class="arrow-right {{ empty($episode->next_episode_url) ? 'arrow-hide' : '' }}">&#8667;</a>
  </div>

  <div class="content-generic">
    @foreach($resolutions as $resolution)
      <ul class="list-group">
        <li class="list-group-item">
          <h2>{{ $resolution }}</h2>
        </li>
        <li class="list-group-item">
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
          <div class="content-close"></div>
        </li>
      </ul>
    @endforeach
    <div class="content-close"></div>
  </div>

  @include('components.malwidget', ['mal_url' => $show->mal_url])

  <div class="content-header">Comments</div>
  <!-- TODO: Disqus integration -->
@endsection
