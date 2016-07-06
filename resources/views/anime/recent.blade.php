@extends('layouts.app')
@section('title', 'Recently Updated')

@section('content-center')
  <div class="content-section">
    <div class="section-heading">Recently Updated</div>
    <div class="section-body">
      @foreach($shows as $show)

      @endforeach
    </div>
  </div>
@endsection
