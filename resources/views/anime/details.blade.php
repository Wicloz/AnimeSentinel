@extends('layouts.app')
@section('title', $show->title)

@section('content-center')
  <div class="content-header">{{ $show->title }}</div>
  <div class="content-generic">
    <ul>
      <li><a target="_blank" href="http://myanimelist.net/anime/{{ $show->mal_id }}">{{ $show->mal_id }}</a></li>
      <li>{{ implode(', ', $show->alts) }}</li>
      <li>{{ $show->description }}</li>
      <li>{{ $show->latest_sub }}</li>
      <li>{{ $show->latest_dub }}</li>
    </ul>
  </div>
@endsection
