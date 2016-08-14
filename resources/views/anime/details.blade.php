@extends('layouts.app')
@section('title', $show->title)

@section('content-left')
  <img class="img-thumbnail details-thumbnail" src="{{ url('/media/thumbnails/'.$show->thumbnail_id) }}" alt="{{ $show->title }} - Thumbnail">
  @include('components.animedetails', ['details' => $show])
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

  @include('components.malwidget_banner', ['mal_url' => $show->mal_url])

  <div class="content-header">Episodes</div>
  <div class="content-generic">
    <h2>Subbed</h2>
    <ul class="list-group episode-list">
      @if($show->videos_initialised && count($show->episodes('sub')) === 0)
        <li class="list-group-item">
          No Episodes Found
        </li>
      @elseif(count($show->episodes('sub')) > 0)
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
      @endif
      @if(!$show->videos_initialised)
        <li class="list-group-item">
          Searching for episodes ...
        </li>
      @endif
      <!-- TODO: add next episode prediction -->
    </ul>
    <div class="content-close"></div>
    <h2>Dubbed</h2>
    <ul class="list-group episode-list">
      @if($show->videos_initialised && count($show->episodes('dub')) === 0)
        <li class="list-group-item">
          No Episodes Found
        </li>
      @elseif(count($show->episodes('dub')) > 0)
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
      @endif
      @if(!$show->videos_initialised)
        <li class="list-group-item">
          Searching for episodes ...
        </li>
      @endif
      <!-- TODO: add next episode prediction -->
    </ul>
    <div class="content-close"></div>
  </div>

  <div class="content-header">Comments</div>
  @if(isset($show->mal_id))
    @include('components.disqus', [
      'disqus_url' => $show->details_url,
      'disqus_id' => 'show:(mal:'.$show->id.')',
    ])
  @else
    @include('components.disqus', [
      'disqus_url' => $show->details_url,
      'disqus_id' => 'show:(id:'.$show->id.')',
    ])
  @endif
@endsection
