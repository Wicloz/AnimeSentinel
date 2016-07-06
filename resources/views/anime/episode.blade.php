@extends('layouts.app')
@section('title', "$show->name - Episode $episode_num")

@section('content-center')
  <div class="content-section">
    <div class="section-heading">{{ $show->name }} - Episode {{ $episode_num }}</div>
    <div class="section-body">
      TODO
    </div>
  </div>
@endsection
