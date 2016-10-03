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
    {{ $details->printAlts() }}
  </p>
  <p>
    <strong>Type:</strong>
    {{ $details->printType() }}
  </p>
  <p>
    <strong>Genres:</strong>
    {{ $details->printGenres() }}
  </p>
  <p>
    <strong>Total Episodes:</strong>
    {{ $details->printTotalEpisodes() }}
  </p>
  <p>
    <strong>Expected Duration:</strong>
    {{ $details->printExpectedDuration() }}
  </p>
  <p>
    <strong>Expected Airing:</strong>
    {{ $details->printExpectedAiring() }}
  </p>
  @if(!empty($details->season))
    <p>
      <strong>Season:</strong>
      {{ $details->printSeason() }}
    </p>
  @endif
  <div class="content-close"></div>
</div>

<div class="content-generic">
  <p>
    <strong>Status (Sub):</strong>
    {{ $details->printStatusSub() }}
  </p>
  <p>
    <strong>Status (Dub):</strong>
    {{ $details->printStatusDub() }}
  </p>
  <p>
    <strong>Latest Episode (Sub):</strong>
    {{ $details->printLatestSub() }}
  </p>
  <p>
    <strong>Latest Episode (Dub):</strong>
    {{ $details->printLatestDub() }}
  </p>
  <p>
    <strong>Average Duration:</strong>
    {{ $details->printAvarageDuration(false) }}
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
