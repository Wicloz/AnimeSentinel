@extends('layouts.app')
@section('title', 'Recently Uploaded')

@section('content-center')
  <div class="content-header">
    Recently Uploaded
    <a class="pull-right" href="{{ url('/anime/recent/grid') }}"><img class="recent-type-icon" src="{{ url('/media/icons/grid.png') }}" alt="Grid"></a>
    <a class="pull-right" href="{{ url('/anime/recent/list') }}"><img class="recent-type-icon" src="{{ url('/media/icons/list.png') }}" alt="List"></a>
  </div>

  @for ($i = 0; $i < count($recent); $i += 6)
    <div class="row">
      @for ($j = 0; $j < 6 && $i + $j < count($recent); $j++)
        <div class="col-sm-2">
          <div class="synblock">
            <div class="synblock-ttype-{{ $recent[$i + $j]->translation_type }}">
              {{ $recent[$i + $j]->translation_type === 'sub' ? 'Subbed' : '' }}{{ $recent[$i + $j]->translation_type === 'dub' ? 'Dubbed' : ''}}
            </div>
            <a href="{{ $recent[$i + $j]->show->details_url }}">
              <img class="img-thumbnail synblock-thumbnail" src="{{ $recent[$i + $j]->show->thumbnail_url }}" alt="{{ $recent[$i + $j]->show->title }} - Thumbnail">
            </a>
            <div class="synblock-episode">
              <a href="{{ $recent[$i + $j]->episode_url }}">Episode {{ $recent[$i + $j]->episode_num }}</a>
            </div>
          </div>
        </div>
      @endfor
    </div>
  @endfor
@endsection
