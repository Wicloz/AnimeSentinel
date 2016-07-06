@extends('layouts.app')
@section('title', 'Recently Updated')

@section('content-center')
  <div class="content-header">Recently Updated</div>
  @foreach($shows as $show)
    <div class="synopsis-panel">
      <div class="row">
        <div class="col-sm-2">
          <img class="img-thumbnail synopsis-thumbnail" src="{{ url('/media/thumbnails/'.$show->id) }}" alt="{{ $show->title }} thumbnail">
        </div>
        <div class="col-sm-10">
          <div class="synopsis-title">{{ $show->title }}</div>
          <div class="synopsis-details">
            {{ $show->description }}
          </div>
          <div class="synopsis-episodes">
            <div class="row">
              <div class="col-sm-6">
                Uploaded Episode Type: {{ $show->this_translation === 'sub' ? 'Subbed' : '' }} {{ $show->this_translation === 'dub' ? 'Dubbed' : ''}}
              </div>
              <div class="col-sm-6">
                Uploaded Episode Number: <a href="{{ url("/anime/$show->id/$show->this_translation/episode-$show->this_episode") }}">{{ $show->this_episode }}</a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  @endforeach
@endsection
