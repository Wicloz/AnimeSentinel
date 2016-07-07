@extends('layouts.app')
@section('title', $show->title)

  @section('content-left')
    <img class="img-thumbnail details-thumbnail" src="{{ url('/media/thumbnails/'.$show->id) }}" alt="{{ $show->title }} - Thumbnail">
    <div class="content-header">
      <a target="_blank" href="{{ $show->mal_url }}">
        View on MyAnimeList
      </a>
    </div>
  @endsection

@section('content-center')
  <div class="content-header">{{ $show->title }}</div>
  <div class="content-generic">
    {!! $show->description !!}
    <div class="content-close"></div>
  </div>

  <div class="content-header">Episodes</div>
  <div class="content-generic">
    <h2>Subbed</h2>
    @if (count($show->episodes_sub) === 0)
      <p>
        No Episodes Found
      </p>
    @else
      <ul class="list-group">
        @foreach($show->episodes_sub as $episode)
          <li class="list-group-item">
            <a href="{{ $episode->episode_url }}">Episode {{ $episode->episode_num }}</a>
          </li>
        @endforeach
      </ul>
    @endif
    <div class="content-close"></div>
    <h2>Dubbed</h2>
    @if (count($show->episodes_dub) === 0)
      <p>
        No Episodes Found
      </p>
    @else
      <ul class="list-group">
        @foreach($show->episodes_dub as $episode)
          <li class="list-group-item">
            <a href="{{ $episode->episode_url }}">Episode {{ $episode->episode_num }}</a>
          </li>
        @endforeach
      </ul>
    @endif
    <div class="content-close"></div>
  </div>

  <div class="content-header">A somewhat working iframe powered MAL widget:</div>
  <div class="content-generic flowfix">
    <div class="mal-widget">
      <iframe src="{{ $show->mal_url }}"></iframe>
    </div>
    <div class="content-close"></div>
  </div>
@endsection
