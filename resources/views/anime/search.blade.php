@extends('layouts.app')
@section('title', 'Search Anime')

@section('content-top')
  <div class="container-fluid">
    <div class="row">
      <form class="searchbar-top" method="POST">
        {{ csrf_field() }}
        <div class="form-group">
          <input type="text" name="q" class="form-control" value="{{ request('q') }}" placeholder="Search ..."></input>
          <button type="submit" class="btn btn-primary pull-right">Search</button>
        </div>
      </form>
    </div>
  </div>
@endsection

@section('content-center')
  @if (count($results) === 0)
    <div class="content-header">No Results</div>
  @else
    <div class="content-header">Search Results</div>
    <div class="content-generic">
      @foreach($results as $result)
        <div class="synopsis-panel">
          <div class="row">
            <div class="col-sm-2">
              @if(empty($result->mal))
                <a href="{{ $result->details_url }}">
                  <img class="img-thumbnail synopsis-thumbnail" src="{{ url('/media/thumbnails/'.$result->thumbnail_id) }}" alt="{{ $result->title }} - Thumbnail">
                </a>
              @else
                <a target="_blank" href="http://myanimelist.net/anime/{{ $result->id }}">
                  <img class="img-thumbnail synopsis-thumbnail" src="{{ $result->image }}" alt="{{ $result->title }} - Thumbnail">
                </a>
              @endif
            </div>
            <div class="col-sm-10">
              <div class="synopsis-title">
                @if(empty($result->mal))
                  <a href="{{ $result->details_url }}">{{ $result->title }}</a>
                @else
                  <a target="_blank" href="http://myanimelist.net/anime/{{ $result->id }}">{{ $result->title }}</a>
                @endif
              </div>
              <div class="synopsis-details">
                @if(empty($result->mal))
                  <div class="collapsed toggle" data-toggle="collapse" data-target="#description-{{ $result->id }}">
                    &laquo; Toggle Description &raquo;
                  </div>
                  <div class="collapse" id="description-{{ $result->id }}">
                    {!! $result->description !!}
                  </div>
                @else
                  <p>This show is not in our database yet.</p>
                  <div class="btn btn-primary">Request Queue</div>
                @endif
              </div>
              @if(empty($result->mal))
                <div class="synopsis-episodes">
                  <div class="row">
                    <div class="col-sm-4">
                      @if(!empty($result->latest_sub))
                        <a href="{{ url("/anime/$result->id/sub/episode-$result->latest_sub") }}">
                          Latest Subbed: Epsiode {{ $result->latest_sub }}
                        </a>
                      @else
                        Latest Subbed: No episodes available
                      @endif
                    </div>
                    <div class="col-sm-4">
                      @if(!empty($result->latest_dub))
                        <a href="{{ url("/anime/$result->id/dub/episode-$result->latest_dub") }}">
                          Latest Dubbed: Epsiode {{ $result->latest_dub }}
                        </a>
                      @else
                        Latest Dubbed: No episodes available
                      @endif
                    </div>
                  </div>
                </div>
              @endif
            </div>
          </div>
        </div>
      @endforeach
      <div class="content-close"></div>
    </div>
  @endif
@endsection
