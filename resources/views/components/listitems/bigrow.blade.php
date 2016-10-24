<div class="item-bigrow">
  <div class="row">

    <div class="col-sm-2">
      @if($isMal)
        <a>
      @else
        <a href="{{ $show->details_url }}">
      @endif
        <img class="img-thumbnail bigrow-thumbnail-wide" src="{{ $show->thumbnail_url }}" alt="{{ $show->title }} - Thumbnail">
      </a>
    </div>

    <div class="col-sm-10">
      <div class="bigrow-title">
        @if($isMal)
          <a>
        @else
          <a href="{{ $show->details_url }}">
        @endif
          <img class="img-thumbnail bigrow-thumbnail-slim" src="{{ $show->thumbnail_url }}" alt="{{ $show->title }} - Thumbnail">
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

      <div class="row">
        <div class="col-sm-9">
          <div class="bigrow-description">
            {!! $show->description !!}
          </div>
        </div>

        <div class="col-sm-3">
          <div class="bigrow-details">
            <p>
              <strong>Type:</strong>
              {{ $show->printType() }}
            </p>
            <p>
              <strong>Genres:</strong>
              {{ $show->printGenres() }}
            </p>
            <p>
              <strong>Total Episodes:</strong>
              {{ $show->printTotalEpisodes() }}
            </p>
            <p>
              <strong>Expected Duration:</strong>
              {{ $show->printExpectedDuration() }}
            </p>
            <p>
              <strong>Expected Airing:</strong>
              {{ $show->printExpectedAiring() }}
            </p>
            @if(!empty($show->season))
              <p>
                <strong>Season:</strong>
                {{ $show->printSeason() }}
              </p>
            @endif
          </div>
        </div>
      </div>

      <div class="bigrow-bottombar">
        <div class="row">
          @if(isset($video))
            <div class="col-sm-4">
              <strong>Episode Type:</strong>
              {{ $video->translation_type === 'sub' ? 'Subbed' : '' }}{{ $video->translation_type === 'dub' ? 'Dubbed' : ''}}
            </div>
            <div class="col-sm-4">
              <strong>Uploaded By:</strong>
              <a href="{{ $video->streamer->details_url }}">{{ $video->streamer->name }}</a>
            </div>
            <div class="col-sm-4">
              <strong>Uploaded On:</strong>
              {{ $video->uploadtime->format('M j, Y (l)') }}
            </div>
          @elseif($isMal)
            <div class="col-sm-4">
              This show is not in our database yet.
            </div>
            <div class="col-sm-4">
              <form action="{{ fullUrl('/anime/add') }}" method="POST">
                {{ csrf_field() }}
                <input type="hidden" name="mal_id" value="{{ $show->mal_id }}"></input>
                <input type="hidden" name="gotodetails" value="0"></input>
                <button type="submit" class="btn btn-default">Add and return to Search Results</button>
              </form>
            </div>
            <div class="col-sm-4">
              <form action="{{ fullUrl('/anime/add') }}" method="POST">
                {{ csrf_field() }}
                <input type="hidden" name="mal_id" value="{{ $show->mal_id }}"></input>
                <input type="hidden" name="gotodetails" value="1"></input>
                <button type="submit" class="btn btn-default">Add and go to Details Page</button>
              </form>
            </div>
          @else
            <div class="col-sm-6">
              @if(isset($show->latest_sub))
                <a href="{{ $show->latest_sub->episode_url }}">
                  <strong>Latest Subbed:</strong> {{ $show->printLatest('sub') }};
                  <strong>Uploaded On:</strong> {{ $show->latest_sub->uploadtime->format('M j, Y (l)') }}
                </a>
              @else
                <strong>Latest Subbed:</strong> {{ $show->printLatest('sub') }}
              @endif
            </div>
            <div class="col-sm-6">
              @if(isset($show->latest_dub))
                <a href="{{ $show->latest_dub->episode_url }}">
                  <strong>Latest Dubbed:</strong> {{ $show->printLatest('dub') }};
                  <strong>Uploaded On:</strong> {{ $show->latest_dub->uploadtime->format('M j, Y (l)') }}
                </a>
              @else
                <strong>Latest Dubbed:</strong> {{ $show->printLatest('dub') }}
              @endif
            </div>
          @endif
        </div>
      </div>
    </div>
  </div>
</div>
