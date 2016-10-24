<div class="table-responsive">
  <table class="table table-striped table-hover table-anime">

    <thead>
      <tr>
        @foreach ($columns as $column)
          <th class="col-{{ $column }}">
          @if ($column === 'thumbnail')
          @elseif ($column === 'title')
            Title
          @elseif ($column === 'description')
            Description
          @elseif ($column === 'type')
            Type
          @elseif ($column === 'genres')
            Genres
          @elseif ($column === 'season')
            Season
          @elseif ($column === 'rating')
            Rating
          @elseif ($column === 'episode_amount')
            Total Episodes
          @elseif ($column === 'episode_duration')
            Expected Duration
          @elseif ($column === 'videos')
            Videos
          @elseif ($column === 'watchable')
            Watchable
          @elseif ($column === 'broadcasts')
            Broadcasts
          @endif
          </th>
        @endforeach
      </tr>
    </thead>

    <tbody>
      @foreach ($shows as $index => $show)
        @if ($show->mal)
          <tr>
        @else
          <tr onclick="window.open('{{ $show->details_url }}', '_self');">
        @endif
          @foreach ($columns as $column)
            <td class="col-{{ $column }}">
              @if ($column === 'thumbnail')
                <img class="img-thumbnail" src="{{ $show->thumbnail_url }}" alt="{{ $show->title }} - Thumbnail">

              @elseif ($column === 'title')
                {{ $show->title }}

              @elseif ($column === 'description')
                <div class="table-description">
                  {!! $show->description !!}
                </div>

              @elseif ($column === 'type')
                {{ $show->printType() }}

              @elseif ($column === 'genres')
                {!! str_replace(', ', ',<br>', $show->printGenres()) !!}

              @elseif ($column === 'season')
                {{ $show->printSeason() }}

              @elseif ($column === 'rating')
                {{ $show->printRating() }}

              @elseif ($column === 'episode_amount')
                {{ $show->printTotalEpisodes() }}

              @elseif ($column === 'episode_duration')
                {{ $show->printExpectedDuration() }}

              @elseif ($column === 'videos')
                @if(isset($videos[$index]) && $videos[$index] !== null)
                  <p>
                    <strong>Episode Number:</strong>
                    {{ $videos[$index]->episode_num }}
                  </p>
                  <p>
                    <strong>Episode Type:</strong>
                    {{ $videos[$index]->translation_type === 'sub' ? 'Subbed' : '' }}{{ $videos[$index]->translation_type === 'dub' ? 'Dubbed' : ''}}
                  </p>
                  <p>
                    <strong>Uploaded By:</strong>
                    <a href="{{ $videos[$index]->streamer->details_url }}">{{ $videos[$index]->streamer->name }}</a>
                  </p>
                  <p>
                    <strong>Uploaded On:</strong>
                    {{ $videos[$index]->uploadtime->format('M j, Y (l)') }}
                  </p>
                  <p>
                    <strong>Episode Duration:</strong>
                    {{ fancyDuration($show->videos()->episode($videos[$index]->translation_type, $videos[$index]->episode_num)->avg('duration')) }}
                  </p>
                @elseif($show->mal)
                  <p>
                    This show is not in our database yet.
                  </p>
                  <form action="{{ fullUrl('/anime/add') }}" method="POST">
                    {{ csrf_field() }}
                    <input type="hidden" name="mal_id" value="{{ $show->mal_id }}"></input>
                    <input type="hidden" name="gotodetails" value="0"></input>
                    <button type="submit" class="btn btn-default btn-sm">Add and return to Search Results</button>
                  </form>
                  <form action="{{ fullUrl('/anime/add') }}" method="POST">
                    {{ csrf_field() }}
                    <input type="hidden" name="mal_id" value="{{ $show->mal_id }}"></input>
                    <input type="hidden" name="gotodetails" value="1"></input>
                    <button type="submit" class="btn btn-default btn-sm last-margin">Add and go to Details Page</button>
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

              @elseif ($column === 'watchable')
                @if (!$show->mal)
                  <ul class="list-unstyled">
                    @if (!$show->videos_initialised)
                      <li class="text-info">
                        - Searching for Episodes -
                      </li>
                    @else
                      @forelse ($show->episodes('sub', 'asc', $show->mal_show->eps_watched) as $episode)
                        @if ($loop->index < Auth::user()->viewsettings_overview->get('cutoff'))
                          <li class="text-warning">
                            <a href="{{ $episode->episode_url }}">
                              Episode {{ $episode->episode_num }}
                            </a>
                          </li>
                        @elseif ($loop->index === Auth::user()->viewsettings_overview->get('cutoff'))
                          <li class="text-warning">
                            - And {{ $show->episodes('sub', 'asc', $show->mal_show->eps_watched)->count() - Auth::user()->viewsettings_overview->get('cutoff') }} more -
                          </li>
                        @endif
                      @empty
                        <li class="text-success">
                          - Up To Date -
                        </li>
                      @endforelse
                      @if($show->printNextUpload('sub') !== 'NA')
                        <li>
                          ETA: {!! $show->printNextUpload('sub', 'M j, Y (l)') !!}
                        </li>
                      @endif
                    @endif
                  </ul>
                @else
                  <form action="{{ fullUrl('/anime/add') }}" method="POST">
                    {{ csrf_field() }}
                    <input type="hidden" name="mal_id" value="{{ $show->mal_id }}"></input>
                    <input type="hidden" name="gotodetails" value="0"></input>
                    <button type="submit" class="btn btn-default btn-sm">Add and return to the Overview</button>
                  </form>
                  <form action="{{ fullUrl('/anime/add') }}" method="POST">
                    {{ csrf_field() }}
                    <input type="hidden" name="mal_id" value="{{ $show->mal_id }}"></input>
                    <input type="hidden" name="gotodetails" value="1"></input>
                    <button type="submit" class="btn btn-default btn-sm last-margin">Add and go to the Details Page</button>
                  </form>
                @endif

              @elseif ($column === 'broadcasts')
                {{ $show->printBroadcasts() }}

              @endif
            </td>
          @endforeach
        </tr>
      @endforeach
    </tbody>
  </table>
</div>
