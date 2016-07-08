@extends('layouts.app')
@section('title', 'Welcome')

@section('content-top')
  <div class="jumbotron jumbotron-welcome stick-to-nav">
    <div class="container">
      <h1>Anime Sentinel</h1>
      <p>
        Ever wanted to watch an older or lesser known anime but you can't find it on your favourite streaming site? Search our database to find out which sites do stream it.
        This site aims to index which anime is available on which streaming sites, making all this information accesible through a single service.
      </p>
      <p>
        Do you want to receive notifications when an anime you're watching has a new episode available? Then sing up and link your MAL account.
        This site will constantly check the 'recently aired' pages of streaming sites, so you can get notified whenever a new episode is uploaded.
      </p>
      <p>
        <a class="btn btn-primary btn-lg" href="{{ url('/about') }}" role="button">Read More &raquo;</a>
      </p>
    </div>
  </div>
@endsection

@section('content-center')
  <div class="welcome-content-wrapper">
    <div class="content-header">Recently Uploaded</div>
    @foreach($recent as $video)
      <div class="synopsis-panel">
        <div class="row">
          <div class="col-sm-2">
            <a href="{{ $video->show->details_url }}">
              <img class="img-thumbnail synopsis-thumbnail" src="{{ $video->show->thumbnail_url }}" alt="{{ $video->show->title }} - Thumbnail">
            </a>
          </div>
          <div class="col-sm-10">
            <div class="synopsis-title"><a href="{{ $video->show->details_url }}">{{ $video->show->title }}</a></div>
            <div class="synopsis-details">
              <div class="collapsed toggle" data-toggle="collapse" data-target="#description-{{ $video->show->id }}-{{ $video->translation_type }}-{{ $video->episode_num }}">
                &laquo; Toggle Description &raquo;
              </div>
              <div class="collapse" id="description-{{ $video->show->id }}-{{ $video->translation_type }}-{{ $video->episode_num }}">
                {!! $video->show->description !!}
              </div>
            </div>
            <div class="synopsis-episodes">
              <div class="row">
                <div class="col-sm-4">
                  <a href="{{ url("/anime/$video->show->id/$video->translation_type/episode-$video->episode_num") }}">
                    Episode {{ $video->episode_num }} Has Aired
                  </a>
                </div>
                <div class="col-sm-4">
                  Uploaded Episode Type: {{ $video->translation_type === 'sub' ? 'Subbed' : '' }} {{ $video->translation_type === 'dub' ? 'Dubbed' : ''}}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    @endforeach
  </div>
@endsection
