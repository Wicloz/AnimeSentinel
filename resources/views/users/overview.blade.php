@extends('layouts.app')
@section('title', 'Anime Overview')

@section('content-center')
  <div class="content-generic">
    @include('components.anime.table', [
      'shows' => $shows,
      'columns' => [
        'thumbnail',
        'title',
        'watchable',
      ],
    ])
    <div class="content-close"></div>
  </div>
@endsection
