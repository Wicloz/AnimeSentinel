@extends('layouts.app')
@section('title', $show->title)

@section('content-left')
  <img class="img-thumbnail details-thumbnail" src="{{ url('/media/thumbnails/'.$show->thumbnail_id) }}" alt="{{ $show->title }} - Thumbnail">
  @include('components.animedetails', ['details' => $show])
  <div class="content-header hide-md">
    <a target="_blank" href="{{ $show->mal_url }}">View on MyAnimeList</a>
  </div>
@endsection

@section('content-center')
  <div class="content-header">{{ $show->title }}</div>
  <div class="content-generic">
    <p>{!! $show->description !!}</p>
    <div class="content-close"></div>
  </div>

  @include('components.malwidget_big', ['mal_url' => $show->mal_url])

  <div class="content-header">Episodes</div>
  <div class="content-generic">
    <h2>Subbed</h2>
    @if(!$show->videos_initialised)
      <ul class="list-group episode-list">
        <li class="list-group-item">
          Searching for episodes ...
        </li>
      </ul>
    @elseif(count($show->episodes('sub')) === 0)
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
                    <li><a href="{{ $streamer->details_url }}">{{ $streamer->name }}</a></li>
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
    @if(!$show->videos_initialised)
      <ul class="list-group episode-list">
        <li class="list-group-item">
          Searching for episodes ...
        </li>
      </ul>
    @elseif(count($show->episodes('dub')) === 0)
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
                    <li><a href="{{ $streamer->details_url }}">{{ $streamer->name }}</a></li>
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

  <div class="content-header">Comments</div>
  @if(isset($show->mal_id))
    @include('components.disqus', [
      'disqus_url' => $show->details_url,
      'disqus_id' => 'mal:'.$show->id,
    ])
  @else
    @include('components.disqus', [
      'disqus_url' => $show->details_url,
      'disqus_id' => 'id:'.$show->id,
    ])
  @endif
@endsection
