<div class="item-smallrow">
  <div class="row">

    <div class="col-sm-1">
      @if($isMal)
        <a>
      @else
        <a href="{{ $show->details_url }}">
      @endif
        <img class="img-thumbnail smallrow-thumbnail-wide" src="{{ $show->thumbnail_url }}" alt="{{ $show->title }} - Thumbnail">
      </a>
    </div>

    <div class="col-sm-8">
      <div class="smallrow-title">
        @if($isMal)
          <a>
        @else
          <a href="{{ $show->details_url }}">
        @endif
          <img class="img-thumbnail smallrow-thumbnail-slim" src="{{ $show->thumbnail_url }}" alt="{{ $show->title }} - Thumbnail">
        </a>
        @if($isMal)
          <a>
        @else
          <a href="{{ $show->details_url }}">
        @endif
          {{ $show->title }}
        </a>
        <span class="pull-right">
          @if (isset($video))
            <a href="{{ $video->episode_url }}">
              Episode {{ $video->episode_num }} {{ $video->translation_type === 'sub' ? '(Sub)' : '' }}{{ $video->translation_type === 'dub' ? '(Dub)' : ''}}
            </a>
          @else
            {{ $show->printStatus('sub') }}
          @endif
        </span>
      </div>

      <div class="smallrow-description">
        {!! $show->description !!}
      </div>

      <div class="smallrow-bottombar">
        <div class="row">
          @if(!empty($show->season))
            <div class="col-sm-2">
              {{ $show->printSeason() }}
            </div>
          @endif
          <div class="col-sm-2">
            <strong>Type:</strong>
            {{ $show->printType() }}
          </div>
          <div class="col-sm-{{ !empty($show->season) ? '8' : '10' }}">
            <strong>Genres:</strong>
            {{ $show->printGenres() }}
          </div>
        </div>
      </div>
    </div>

    <div class="col-sm-3">
      <div class="smallrow-details">
        <p>
          <strong>Total Episodes:</strong>
          {{ $show->printTotalEpisodes() }}
        </p>
        <p>
          <strong>Expected Duration:</strong>
          {{ $show->printExpectedDuration() }}
        </p>
      </div>

      <div class="smallrow-details">
        @if (isset($video))
          <p>
            <a href="{{ $video->episode_url }}">
              <strong>
                Episode {{ $video->episode_num }} Has Aired
              </strong>
            </a>
          </p>
          <p>
            <strong>Episode Type:</strong>
            {{ $video->translation_type === 'sub' ? 'Subbed' : '' }}{{ $video->translation_type === 'dub' ? 'Dubbed' : ''}}
          </p>
          <p>
            <strong>Uploaded By:</strong>
            <a href="{{ $video->streamer->details_url }}">{{ $video->streamer->name }}</a>
          </p>
          <p>
            <strong>Uploaded On:</strong>
            {{ $video->uploadtime->format('M j, Y (l)') }}
          </p>
        @elseif ($isMal)
          <p>
            This show is not in our database yet.
          </p>
          <form action="{{ fullUrl('/anime/add') }}" method="POST">
            {{ csrf_field() }}
            <input type="hidden" name="mal_id" value="{{ $show->mal_id }}"></input>
            <input type="hidden" name="gotodetails" value="0"></input>
            <button type="submit" class="btn btn-default">Add and return to Search Results</button>
          </form>
          <form action="{{ fullUrl('/anime/add') }}" method="POST">
            {{ csrf_field() }}
            <input type="hidden" name="mal_id" value="{{ $show->mal_id }}"></input>
            <input type="hidden" name="gotodetails" value="1"></input>
            <button type="submit" class="btn btn-default">Add and go to Details Page</button>
          </form>
        @else
          <p>
            @if(isset($show->latest_sub))
              <a href="{{ $show->latest_sub->episode_url }}">
                <strong>Latest Subbed:</strong> {{ $show->printLatest('sub') }}
              </a>
            </p><p>
              <strong>Uploaded On:</strong> {{ $show->latest_sub->uploadtime->format('M j, Y (l)') }}
            @else
              <strong>Latest Subbed:</strong> {{ $show->printLatest('sub') }}
            @endif
          </p>
          <p>
            @if(isset($show->latest_dub))
              <a href="{{ $show->latest_dub->episode_url }}">
                <strong>Latest Dubbed:</strong> {{ $show->printLatest('dub') }}
              </a>
            </p><p>
              <strong>Uploaded On:</strong> {{ $show->latest_dub->uploadtime->format('M j, Y (l)') }}
            @else
              <strong>Latest Dubbed:</strong> {{ $show->printLatest('dub') }}
            @endif
          </p>
        @endif
      </div>
    </div>

  </div>
</div>
