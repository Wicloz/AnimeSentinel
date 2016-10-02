<div class="item-smallrow">
  <div class="row">

    <div class="col-sm-1">
      <a {{ $isMal ? 'target="_blank"' : '' }} href="{{ $show->details_url }}">
        <img class="img-thumbnail smallrow-thumbnail-wide" src="{{ $show->thumbnail_url }}" alt="{{ $show->title }} - Thumbnail">
      </a>
    </div>

    <div class="col-sm-8">
      <div class="smallrow-title">
        <a {{ $isMal ? 'target="_blank"' : '' }} href="{{ $show->details_url }}">
          <img class="img-thumbnail smallrow-thumbnail-slim" src="{{ $show->thumbnail_url }}" alt="{{ $show->title }} - Thumbnail">
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

      <div class="smallrow-description">
        {!! $show->description !!}
      </div>

      <div class="smallrow-bottombar">
        <div class="row">
          @if(!empty($show->season))
            <div class="col-sm-2">
              {{ ucwords($show->season) }}
            </div>
          @endif
          <div class="col-sm-2">
            <strong>Type:</strong>
            {{ isset($show->type) ? ucwords($show->type) : 'Unknown' }}
          </div>
          <div class="col-sm-{{ !empty($show->season) ? '8' : '10' }}">
            <strong>Genres:</strong>
            @if(isset($show->genres) && count($show->genres) > 0)
              @foreach($show->genres as $index => $genre)
                {{ ucwords($genre) }}{{ $index === count($show->genres) -1 ? '' : ',' }}
              @endforeach
            @else
              Unknown
            @endif
          </div>
        </div>
      </div>
    </div>

    <div class="col-sm-3">
      <div class="smallrow-details">
        <p>
          <strong>Total Episodes:</strong>
          {{ $show->episode_amount or 'Unknown' }}
        </p>
        <p>
          <strong>Expected Duration:</strong>
          @if(isset($show->episode_duration))
            {{ fancyDuration($show->episode_duration * 60, false) }} per ep.
          @elseif(!$isMal && $show->videos()->avg('duration') !== null)
            {{ fancyDuration($show->videos()->avg('duration')) }}
          @else
            Unknown
          @endif
        </p>
      </div>

      <div class="smallrow-details">
        @if (isset($video))
          <p>
            <a href="{{ $video->episode_url }}">
              Episode {{ $video->episode_num }} Has Aired
            </a>
          </p>
          <p>
            Episode Type: {{ $video->translation_type === 'sub' ? 'Subbed' : '' }}{{ $video->translation_type === 'dub' ? 'Dubbed' : ''}}
          </p>
          <p>
            Uploaded by <a href="{{ $video->streamer->details_url }}">{{ $video->streamer->name }}</a>
          </p>
          <p>
            Uploaded on {{ $video->uploadtime->format('M j, Y (l)') }}
          </p>
        @elseif (!$isMal)
          <p>
            @if(!isset($show->latest_sub))
              @if(!$show->videos_initialised)
                Latest Subbed: Searching for Episodes ...
              @else
                Latest Subbed: No Episodes Available
              @endif
            @else
              <a href="{{ $show->latest_sub->episode_url }}">
                Latest Subbed: Epsiode {{ $show->latest_sub->episode_num }}
              </a>
            </p><p>
              Uploaded On: {{ $show->latest_sub->uploadtime->format('M j, Y (l)') }}
            @endif
          </p>
          <p>
            @if(!isset($show->latest_dub))
              @if(!$show->videos_initialised)
                Latest Dubbed: Searching for Episodes ...
              @else
                Latest Dubbed: No Episodes Available
              @endif
            @else
              <a href="{{ $show->latest_dub->episode_url }}">
                Latest Dubbed: Epsiode {{ $show->latest_dub->episode_num }}
              </a>
            </p><p>
              Uploaded On: {{ $show->latest_dub->uploadtime->format('M j, Y (l)') }}
            @endif
          </p>
        @else
          <p>
            This show is not in our database yet.
          </p>
          <form action="{{ fullUrl('/anime/add') }}" method="POST">
            {{ csrf_field() }}
            <input type="hidden" name="mal_id" value="{{ $show->mal_id }}"></input>
            <input type="hidden" name="gotodetails" value="0"></input>
            <button type="submit" class="btn btn-primary">Add and return to Search Results</button>
          </form>
          <form action="{{ fullUrl('/anime/add') }}" method="POST">
            {{ csrf_field() }}
            <input type="hidden" name="mal_id" value="{{ $show->mal_id }}"></input>
            <input type="hidden" name="gotodetails" value="1"></input>
            <button type="submit" class="btn btn-primary">Add and go to Details Page</button>
          </form>
        @endif
      </div>
    </div>

  </div>
</div>
