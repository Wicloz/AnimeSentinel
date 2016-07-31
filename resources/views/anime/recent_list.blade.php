@extends('layouts.app')
@section('title', 'Recently Uploaded')

@section('content-center')
  <div class="content-header">
    Recently Uploaded
    <a class="pull-right" href="{{ url('/anime/recent/grid') }}"><img class="recent-type-icon" src="{{ url('/media/icons/grid.png') }}" alt="Grid"></a>
    <a class="pull-right" href="{{ url('/anime/recent/list') }}"><img class="recent-type-icon" src="{{ url('/media/icons/list.png') }}" alt="List"></a>
  </div>

  @foreach($recent as $video)
    @include('components.synopsis', [
      'syn_mal' => false,
      'syn_show' => $video->show,
      'syn_unique' => $video->show->id.'-'.$video->translation_type.'-'.$video->episode_num,
      'syn_video' => $video,
    ])
  @endforeach
@endsection
