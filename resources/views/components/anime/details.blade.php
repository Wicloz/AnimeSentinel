<div class="content-header">
  @if(!empty($link))
    <a href="{{ $details->details_url }}">Information</a>
  @else
    Information
  @endif
</div>

<div class="content-generic">
  <p>
    <strong>Alternative Titles:</strong>
    {{ implode(', ', $details->alts) }}
  </p>
  <p>
    <strong>Type:</strong>
    {{ isset($details->type) ? ucwords($details->type) : 'Unknown' }}
  </p>
  <p>
    <strong>Genres:</strong>
    @if(count($details->genres) > 0)
      @foreach($details->genres as $index => $genre)
        {{ ucwords($genre) }}{{ $index === count($details->genres) -1 ? '' : ',' }}
      @endforeach
    @else
      Unknown
    @endif
  </p>
  <p>
    <strong>Total Episodes:</strong>
    {{ $details->episode_amount or 'Unknown' }}
  </p>
  <p>
    <strong>Expected Duration:</strong>
    @if(isset($details->episode_duration))
      {{ fancyDuration($details->episode_duration * 60, false) }} per ep.
    @else
      Unknown
    @endif
  </p>
  <p>
    <strong>Expected Airing:</strong>
    @if(empty($details->airing_start) && empty($details->airing_end))
      Unknown
    @else
      {{ !empty($details->airing_start) ? $details->airing_start->toFormattedDateString() : '?' }} to {{ !empty($details->airing_end) ? $details->airing_end->toFormattedDateString() : '?' }}
    @endif
  </p>
  @if(!empty($details->season))
    <p>
      <strong>Season:</strong>
      {{ ucwords($details->season) }}
    </p>
  @endif
  <div class="content-close"></div>
</div>

<div class="content-generic">
  <p>
    <strong>Status (Sub):</strong>
    @if($details->isAiring('sub'))
      Currently Airing
    @elseif($details->finishedAiring('sub'))
      Completed
    @else
      Upcoming
    @endif
  </p>
  <p>
    <strong>Status (Dub):</strong>
    @if($details->isAiring('dub'))
      Currently Airing
    @elseif($details->finishedAiring('dub'))
      Completed
    @else
      Upcoming
    @endif
  </p>
  <p>
    <strong>Current Episodes (Sub):</strong>
    {{ $details->latest_sub->episode_num or '0'}}
  </p>
  <p>
    <strong>Current Episodes (Dub):</strong>
    {{ $details->latest_dub->episode_num or '0'}}
  </p>
  <p>
    <strong>Average Duration:</strong>
    @if($details->videos()->avg('duration') !== null)
      {{ fancyDuration($details->videos()->avg('duration')) }}
    @else
      NA
    @endif
  </p>
  <p>
    <strong>First Upload:</strong>
    {{ isset($details->first_video) ? $details->first_video->uploadtime->toDayDateTimeString() : 'NA'}}
  </p>
  <p>
    <strong>Last Upload:</strong>
    {{ isset($details->last_video) ? $details->last_video->uploadtime->toDayDateTimeString() : 'NA' }}
  </p>
  <div class="content-close"></div>
</div>
