@extends('layouts.app')
@section('title', 'Home')

@section('content-top')
  <div class="jumbotron jumbotron-welcome stick-to-nav">
    <div class="container">
      <h1>Anime Sentinel</h1>
      <p>
        Ever wanted to watch an older or lesser known anime but you can't find it on your favourite streaming site? Search our database to find out which sites do stream it.
        This site aims to index which anime is available on which streaming sites, making all this information accesible through a single service.
      </p>
      <p>
        Do you want to recieve notifications when an anime you're watching has a new episode available? Then sing up and link your MAL account.
        This site will constantly check the 'recently aired' pages of streaming sites, so you can get notified whenever a new episode is uploaded.
      </p>
      <p>
        <a class="btn btn-primary btn-lg" href="{{ url('/about') }}" role="button">Read More &raquo;</a>
      </p>
    </div>
  </div>
@endsection

@section('content-center')
  <div class="content-section content-section-welcome">
    <div class="section-heading">Recently Updated</div>
    <div class="section-body">
      @foreach($shows as $show)
        <div class="row synopsis-panel">
          <div class="col-md-2">
            <img class="img-thumbnail" src="" alt="{{ $show->title }} thumbnail">
          </div>
          <div class="col-md-10">
            <div class="synopsis-title">{{ $show->title }}</div>
            <div class="synopsis-details">
              {{ $show->description }}
            </div>
          </div>
        </div>
      @endforeach
    </div>
  </div>
@endsection
