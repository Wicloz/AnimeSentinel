@extends('layouts.app')
@section('title', $show->title)

@section('content-left')
  <img class="img-thumbnail details-thumbnail" src="{{ fullUrl('/media/thumbnails/'.$show->thumbnail_id) }}" alt="{{ $show->title }} - Thumbnail">
  @include('components.anime.details', ['details' => $show])
  @if(isset($show->mal_url))
    <div class="content-header">
      <a target="_blank" href="{{ $show->mal_url }}">View on MyAnimeList</a>
    </div>
  @endif
@endsection

@section('content-center')
  <div class="content-header">{{ $show->title }}</div>
  <div class="content-generic">
    <p>{!! $show->description !!}</p>
    <div class="content-close"></div>
  </div>

  @include('components.mal.widgets.banner', ['mal_url' => $show->mal_url])

  <div class="content-header">Episodes</div>
  <div class="content-generic">
    <h2 style="margin-top:5px;">Subbed</h2>
    <ul class="list-group episode-list">
      @if(count($show->episodes('sub')) > 0)
        @foreach($show->episodes('sub')->load('show') as $episode)
          <li class="list-group-item">
            <div class="row">
              <div class="col-sm-4">
                <div class="row">
                  <div class="col-xs-6">
                    <a href="{{ $episode->episode_url }}">Episode {{ $episode->episode_num }}</a>
                  </div>
                  <div class="col-xs-6">
                    @if(isset($show->mal_show))
                      <span class="status-pull-right">
                        {{ $episode->episode_num <= $show->mal_show->eps_watched ? '✔ Watched' : '' }}
                      </span>
                    @endif
                  </div>
                </div>
              </div>
              <div class="col-sm-8">
                <ul class="streamer-pull-right">
                  @foreach($episode->streamers as $streamer)
                    <li><a href="{{ $streamer->details_url }}">{{ $streamer->name }}</a></li>
                  @endforeach
                </ul>
              </div>
            </div>
          </li>
        @endforeach
        @if(!$show->videos_initialised)
          <li class="list-group-item">
            Searching for more episodes ...
          </li>
        @endif
      @else
        @if($show->videos_initialised)
          <li class="list-group-item">
            No Episodes Found
          </li>
        @elseif(!$show->videos_initialised)
          <li class="list-group-item">
            Searching for episodes ...
          </li>
        @endif
      @endif
      <!-- TODO: add next episode prediction -->
    </ul>
    <div class="content-close"></div>
    <h2>Dubbed</h2>
    <ul class="list-group episode-list">
      @if(count($show->episodes('dub')) > 0)
        @foreach($show->episodes('dub')->load('show') as $episode)
          <li class="list-group-item">
            <div class="row">
              <div class="col-sm-4">
                <div class="row">
                  <div class="col-xs-6">
                    <a href="{{ $episode->episode_url }}">Episode {{ $episode->episode_num }}</a>
                  </div>
                  <div class="col-xs-6">
                    @if(isset($show->mal_show))
                      <span class="status-pull-right">
                        {{ $episode->episode_num <= $show->mal_show->eps_watched ? '✔ Watched' : '' }}
                      </span>
                    @endif
                  </div>
                </div>
              </div>
              <div class="col-sm-8">
                <ul class="streamer-pull-right">
                  @foreach($episode->streamers as $streamer)
                    <li><a href="{{ $streamer->details_url }}">{{ $streamer->name }}</a></li>
                  @endforeach
                </ul>
              </div>
            </div>
          </li>
        @endforeach
        @if(!$show->videos_initialised)
          <li class="list-group-item">
            Searching for more episodes ...
          </li>
        @endif
      @else
        @if($show->videos_initialised)
          <li class="list-group-item">
            No Episodes Found
          </li>
        @elseif(!$show->videos_initialised)
          <li class="list-group-item">
            Searching for episodes ...
          </li>
        @endif
      @endif
      <!-- TODO: add next episode prediction -->
    </ul>
    <div class="content-close"></div>
  </div>

  <div class="content-header">Comments</div>
  @if(isset($show->mal_id))
    @include('components.disqus', [
      'disqus_url' => $show->details_url_static,
      'disqus_id' => 'show:('.json_encode(['mal_id' => $show->mal_id]).')',
    ])
  @else
    @include('components.disqus', [
      'disqus_url' => $show->details_url_static,
      'disqus_id' => 'show:('.json_encode(['show_id' => $show->show_id]).')',
    ])
  @endif
@endsection
