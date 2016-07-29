@extends('layouts.app')
@section('title', $show->title)

@section('content-left')
  <img class="img-thumbnail details-thumbnail" src="{{ url('/media/thumbnails/'.$show->thumbnail_id) }}" alt="{{ $show->title }} - Thumbnail">

  <div class="content-header">Information</div>
  <div class="content-generic">
    <p><strong>Type:</strong> {{ ucwords($show->type) }}</p>
    <p>
      <strong>Genres:</strong>
      @foreach($show->genres as $index => $genre)
        {{ ucwords($genre) }}{{ $index === count($show->genres) -1 ? '' : ',' }}
      @endforeach
    </p>
    <p>
      <strong>Status:</strong>
      @if(!isset($show->latest_sub))
        Upcoming
      @elseif(!isset($show->episode_amount))
        Unknown
      @elseif($show->latest_sub >= $show->episode_amount)
        Completed
      @else
        Current
      @endif
    </p>
    <p><strong>Episodes:</strong> {{ $show->episode_amount or 'Unknown' }}</p>
    <p>
      <strong>Duration:</strong>
      @if(isset($show->episode_duration))
        {{ $show->episode_duration }} min. per ep.
      @else
        Unknown
      @endif
    </p>
    <p>
      <strong>Aired Since:</strong>
      @if(!empty($show->first_video))
        {{ $show->first_video->uploadtime->toFormattedDateString() }}
      @else
        Upcoming
      @endif
    </p>
    <div class="content-close"></div>
  </div>

  <div class="content-header">
    <a target="_blank" href="{{ $show->mal_url }}">View on MyAnimeList</a>
  </div>
@endsection

@section('content-center')
  <div class="content-header">{{ $show->title }}</div>
  <div class="content-generic">
    <p>{!! $show->description !!}</p>
    <div class="content-close"></div>
  </div>

  <div class="content-header">Episodes</div>
  <div class="content-generic">
    <h2>Subbed</h2>
    @if(count($show->episodes('sub')) === 0)
      <ul class="list-group episode-list">
        <li class="list-group-item">
          No Episodes Found
        </li>
      </ul>
    @else
      <ul class="list-group episode-list">
        @foreach($show->episodes('sub') as $episode)
          <li class="list-group-item">
            <div class="row">
              <div class="col-xs-6">
                <a href="{{ $episode->episode_url }}">Episode {{ $episode->episode_num }}</a>
              </div>
              <div class="col-xs-6">
                <ul class="pull-right">
                  @foreach($episode->streamers as $streamer)
                    <!-- TODO: link to streamer page -->
                    <li>{{ $streamer->name }}</li>
                  @endforeach
                </ul>
              </div>
            </div>
          </li>
        @endforeach
        <!-- TODO: add next episode prediction -->
      </ul>
    @endif
    <div class="content-close"></div>
    <h2>Dubbed</h2>
    @if(count($show->episodes('dub')) === 0)
      <ul class="list-group episode-list">
        <li class="list-group-item">
          No Episodes Found
        </li>
      </ul>
    @else
      <ul class="list-group episode-list">
        @foreach($show->episodes('dub') as $episode)
          <li class="list-group-item">
            <div class="row">
              <div class="col-xs-6">
                <a href="{{ $episode->episode_url }}">Episode {{ $episode->episode_num }}</a>
              </div>
              <div class="col-xs-6">
                <ul class="pull-right">
                  @foreach($episode->streamers as $streamer)
                    <!-- TODO: link to streamer page -->
                    <li>{{ $streamer->name }}</li>
                  @endforeach
                </ul>
              </div>
            </div>
          </li>
        @endforeach
        <!-- TODO: add next episode prediction -->
      </ul>
    @endif
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
