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
  @if($details->printBroadcasts() !== 'NA')
    <p>
      <strong>Broadcasts:</strong>
      {{ $details->printBroadcasts() }}
    </p>
  @endif
  @if(!empty($details->season))
    <p>
      <strong>Season:</strong>
      {{ $details->printSeason() }}
    </p>
  @endif
  <p>
    <strong>Rating:</strong>
    {{ $details->printRating() }}
  </p>
  <div class="content-close"></div>
</div>

<div class="content-generic">
  <p>
    <strong>Status (Sub):</strong>
    {{ $details->printStatus('sub') }}
  </p>
  <p>
    <strong>Status (Dub):</strong>
    {{ $details->printStatus('dub') }}
  </p>
  <p>
    <strong>Latest Episode (Sub):</strong>
    {{ $details->printLatest('sub') }}
  </p>
  <p>
    <strong>Latest Episode (Dub):</strong>
    {{ $details->printLatest('dub') }}
  </p>
  <p>
    <strong>Average Duration:</strong>
    {{ $details->printAvarageDuration(false) }}
  </p>
  <div class="content-close"></div>
</div>
