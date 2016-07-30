@extends('layouts.app')
@section('title', 'Recently Uploaded')

@section('content-center')
  <div class="content-header">Recently Uploaded</div>
  @foreach($recent as $video)
    @include('components.synopsis', [
      'syn_mal' => false,
      'syn_show' => $video->show,
      'syn_unique' => $video->show->id.'-'.$video->translation_type.'-'.$video->episode_num,
      'syn_video' => $video,
    ])
  @endforeach
@endsection
