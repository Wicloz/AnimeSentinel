@extends('layouts.app')
@section('title', $show->title.' - '.($video->translation_type === 'sub' ? 'Subbed' : '').($video->translation_type === 'dub' ? 'Dubbed' : '').' - Episode '.$video->episode_num.' '.$video->notes.' - Watch Online in '.$video->resolution)

@section('content-left')
  <a href="{{ $show->details_url }}">
    <img class="img-thumbnail details-thumbnail-wide hidden-xs hidden-sm" src="{{ $show->thumbnail_url }}" alt="{{ $show->title }} - Thumbnail">
  </a>
  @include('components.anime.details', ['details' => $show, 'link' => true])
  @if(isset($show->mal_url))
    <div class="content-header">
      <a target="_blank" href="{{ $show->mal_url }}">View on MyAnimeList</a>
    </div>
  @endif
@endsection

@section('content-center')
  <div class="content-header header-center">
    <a href="{{ $video->pre_episode_url }}" class="arrow-left {{ empty($video->pre_episode_url) ? 'arrow-hide' : '' }}">&#8666;</a>
    <a href="{{ $show->details_url }}">{{ $show->title }}</a>
    - {{ $video->translation_type === 'sub' ? 'Subbed' : '' }}{{ $video->translation_type === 'dub' ? 'Dubbed' : '' }} -
    Episode {{ $video->episode_num }}
    <!-- TODO: select episodes with dropdown -->
    <a href="{{ $video->next_episode_url }}" class="arrow-right {{ empty($video->next_episode_url) ? 'arrow-hide' : '' }}">&#8667;</a>
  </div>

  <div class="content-generic">
    <div class="streamplayer">
      <div style="padding-top:{{ $video->video_aspect * 100 }}%;"></div>
      @if($video->encoding === 'broken')
        <div class="streamplayer-video streamplayer-broken">
          <a href="{{ fullUrl('/about/broken') }}">
            <p class="streamplayer-broken-text">
              This video appears to be unreachable right now, please try another mirror or try again later.
            </p>
          </a>
        </div>
      @elseif($video->player_support)
        <video class="streamplayer-video" controls>
          <source src="{{ $video->link_video }}" type="{{ $video->encoding }}">
          <source src="{{ $video->link_video }}" type="video/mp4">
          <source src="{{ $video->link_video }}" type="video/ogg">
          <source src="{{ $video->link_video }}" type="video/webm">
          Your browser does not support the video tag.
        </video>
      @else
        <iframe class="streamplayer-video" src="{{ $video->link_video }}" scrolling="no"></iframe>
      @endif
    </div>
  </div>

  <div class="content-generic">
    @foreach($resolutions as $resolution)
      <ul class="list-group">
        <li class="list-group-item">
          <h2>{{ $resolution }}</h2>
        </li>
        <li class="list-group-item">
          <div class="row">
            @foreach($mirrors as $mirror)
              @if($mirror->resolution === $resolution)
                <div class="col-sm-4">
                  <a class="episode-block {{ $mirror->id === $video->id ? 'episode-block-active' : '' }}" href="{{ $mirror->stream_url }}">
                    <div class="row">
                      <div class="col-xs-8">
                        <p><strong>Original Streamer:</strong> {{ $mirror->streamer->name }}</p>
                        @if($mirror->player_support)
                          <p class="episode-info-good"><strong>HTML5 Player:</strong> Yes</p>
                        @else
                          <p class="episode-info-bad"><strong>HTML5 Player:</strong> No</p>
                        @endif
                        <p><strong>Duration:</strong> {{ isset($mirror->duration) ? fancyDuration($mirror->duration) : 'Unknown' }}</p>
                        <p><strong>Time 1:</strong> {{ isset($mirror->test1) ? $mirror->test1->toDateTimeString() : 'Unknown' }}</p>
                        <p><strong>Time 2:</strong> {{ isset($mirror->test2) ? $mirror->test2->toDateTimeString() : 'Unknown' }}</p>
                      </div>
                      <div class="col-xs-4 align-center">
                        @if(!empty($mirror->notes))
                          <p><strong>- Notes -</strong></p>
                          <p class="{{ badNotes($mirror->notes) ? 'episode-info-bad' : '' }}">{{ $mirror->notes }}</p>
                        @endif
                        @if($mirror->encoding === 'broken')
                          <p class="episode-info-bad"><strong>- Broken -</strong></p>
                        @elseif($mirror->encoding === null)
                          <p><strong>- Initialising Metadata -</strong></p>
                        @endif
                      </div>
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

  <div class="content-header">Comments</div>
  @include('components.disqus', [
    'disqus_url' => $video->episode_url_static,
    'disqus_id' => 'episode:('.$video->episode_id.')',
  ])
@endsection

@section('content-right')
  <div class="content-header">
    Administration
  </div>
  <div class="content-generic">
    <form action="{{ fullUrl('/anime/reprocess') }}" method="POST">
      {{ csrf_field() }}
      <input type="hidden" name="video_id" value="{{ $video->id }}"></input>
      <button type="submit" class="btn btn-default btn-block">Reprocess this Episode</button>
    </form>
    <p></p>
    <p class="align-center">
      Note: This can take up to 30 seconds.
    </p>
    <div class="content-close"></div>
  </div>

  @if(isset($show->mal_url))
    <div class="content-header">
      <a target="_blank" href="{{ $show->mal_edit_url }}">Edit MAL Details</a>
    </div>
    @if(Auth::check() && Auth::user()->mal_canwrite)
      @include('components.mal.editshow', ['show' => $show])
      @if(isset($show->mal_show))
        <div class="content-generic">
          <form action="{{ fullUrl('/user/setmal/progres') }}" method="POST">
            {{ csrf_field() }}
            <input type="hidden" name="show_id" value="{{ $show->id }}">
            <input type="hidden" name="eps_watched" value="{{ $video->episode_num }}">
            <button type="submit" class="btn btn-default btn-block">Mark this Episode as Watched</button>
          </form>
        </div>
      @endif
    @else
      @include('components.mal.widgets.sidebar', ['mal_url' => $show->mal_url])
    @endif
  @endif
@endsection
