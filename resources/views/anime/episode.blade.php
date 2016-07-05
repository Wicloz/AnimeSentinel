@extends('layouts.app')
@section('title', " $show->name - episode $episode_num")

@section('content-center')
  <div class="content-section">
    <div class="section-heading">{{ $show->name }} episode {{ $episode_num }}</div>
    <div class="section-body">

    </div>
  </div>
@endsection
