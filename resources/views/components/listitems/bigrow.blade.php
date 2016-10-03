<div class="item-bigrow">
  <div class="row">

    <div class="col-sm-2">
      <a {{ $isMal ? 'target="_blank"' : '' }} href="{{ $show->details_url }}">
        <img class="img-thumbnail bigrow-thumbnail-wide" src="{{ $show->thumbnail_url }}" alt="{{ $show->title }} - Thumbnail">
      </a>
    </div>

    <div class="col-sm-10">
      <div class="bigrow-title">
        <a {{ $isMal ? 'target="_blank"' : '' }} href="{{ $show->details_url }}">
          <img class="img-thumbnail bigrow-thumbnail-slim" src="{{ $show->thumbnail_url }}" alt="{{ $show->title }} - Thumbnail">
        </a>
        <a {{ $isMal ? 'target="_blank"' : '' }} href="{{ $show->details_url }}">{{ $show->title }}</a>
        <span class="pull-right">
          @if (isset($video))
            <a href="{{ $video->episode_url }}">
              Episode {{ $video->episode_num }} {{ $video->translation_type === 'sub' ? '(Sub)' : '' }}{{ $video->translation_type === 'dub' ? '(Dub)' : ''}}
            </a>
          @elseif ($isMal)
            @if(!isset($show->airing_start) || Carbon\Carbon::now()->endOfDay()->lt($show->airing_start))
              Upcoming
            @elseif(!isset($show->airing_end) || Carbon\Carbon::now()->startOfDay()->lte($show->airing_end))
              Currently Airing
            @else
              Completed
            @endif
          @else
            @if($show->isAiring('sub'))
              Currently Airing
            @elseif($show->finishedAiring('sub'))
              Completed
            @else
              Upcoming
            @endif
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
              {{ isset($show->type) ? ucwords($show->type) : 'Unknown' }}
            </p>
            <p>
              <strong>Genres:</strong>
              @if(isset($show->genres) && count($show->genres) > 0)
                @foreach($show->genres as $index => $genre)
                  {{ ucwords($genre) }}{{ $index === count($show->genres) -1 ? '' : ',' }}
                @endforeach
              @else
                Unknown
              @endif
            </p>
            <p>
              <strong>Total Episodes:</strong>
              {{ $show->episode_amount or 'Unknown' }}
            </p>
            <p>
              <strong>Expected Duration:</strong>
              @if(isset($show->episode_duration))
                {{ fancyDuration($show->episode_duration * 60, false) }} per ep.
              @elseif(!$isMal && $show->videos()->avg('duration') !== null)
                {{ fancyDuration($show->videos()->avg('duration'), false) }} per ep.
              @else
                Unknown
              @endif
            </p>
            <p>
              <strong>Expected Airing:</strong>
              @if(empty($show->airing_start) && empty($show->airing_end))
                Unknown
              @else
                {{ !empty($show->airing_start) ? $show->airing_start->toFormattedDateString() : '?' }} to {{ !empty($show->airing_end) ? $show->airing_end->toFormattedDateString() : '?' }}
              @endif
            </p>
            @if(!empty($show->season))
              <p>
                <strong>Season:</strong>
                {{ ucwords($show->season) }}
              </p>
            @endif
          </div>
        </div>
      </div>

      <div class="bigrow-bottombar">
        <div class="row">
          @if(isset($video))
            <div class="col-sm-4">
              Episode Type: {{ $video->translation_type === 'sub' ? 'Subbed' : '' }}{{ $video->translation_type === 'dub' ? 'Dubbed' : ''}}
            </div>
            <div class="col-sm-4">
              Uploaded by <a href="{{ $video->streamer->details_url }}">{{ $video->streamer->name }}</a>
            </div>
            <div class="col-sm-4">
              Uploaded on {{ $video->uploadtime->format('M j, Y (l)') }}
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
                <button type="submit" class="btn btn-primary">Add and return to Search Results</button>
              </form>
            </div>
            <div class="col-sm-4">
              <form action="{{ fullUrl('/anime/add') }}" method="POST">
                {{ csrf_field() }}
                <input type="hidden" name="mal_id" value="{{ $show->mal_id }}"></input>
                <input type="hidden" name="gotodetails" value="1"></input>
                <button type="submit" class="btn btn-primary">Add and go to Details Page</button>
              </form>
            </div>
          @else
            <div class="col-sm-6">
              @if(!isset($show->latest_sub))
                @if(!$show->videos_initialised)
                  Latest Subbed: Searching for episodes ...
                @else
                  Latest Subbed: No episodes available
                @endif
              @else
                <a href="{{ $show->latest_sub->episode_url }}">
                  Latest Subbed: Epsiode {{ $show->latest_sub->episode_num }}; Uploaded on {{ $show->latest_sub->uploadtime->format('M j, Y (l)') }}
                </a>
              @endif
            </div>
            <div class="col-sm-6">
              @if(!isset($show->latest_dub))
                @if(!$show->videos_initialised)
                  Latest Dubbed: Searching for episodes ...
                @else
                  Latest Dubbed: No episodes available
                @endif
              @else
                <a href="{{ $show->latest_dub->episode_url }}">
                  Latest Dubbed: Epsiode {{ $show->latest_dub->episode_num }}; Uploaded on {{ $show->latest_dub->uploadtime->format('M j, Y (l)') }}
                </a>
              @endif
            </div>
          @endif
        </div>
      </div>
    </div>
  </div>
</div>
