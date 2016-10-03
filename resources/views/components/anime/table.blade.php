<div class="table-responsive">
  <table class="table table-striped table-hover table-anime">

    <thead>
      <tr>
        @foreach ($columns as $column)
          <th class="col-{{ $column }}">
          @if ($column === 'thumbnail')
          @elseif ($column === 'title')
            Title
          @elseif ($column === 'type')
            Type
          @elseif ($column === 'videos')
            Videos
          @endif
          </th>
        @endforeach
      </tr>
    </thead>

    <tbody>
      @foreach ($shows as $index => $show)
        @if ($show->mal)
          <tr onclick="window.open('{{ $show->details_url }}', '_blank');">
        @else
          <tr onclick="window.open('{{ $show->details_url }}', '_self');">
        @endif
          @foreach ($columns as $column)
            <td class="col-{{ $column }}">
              @if ($column === 'thumbnail')
                <img class="img-thumbnail" src="{{ $show->thumbnail_url }}" alt="{{ $show->title }} - Thumbnail">

              @elseif ($column === 'title')
                {{ $show->title }}

              @elseif ($column === 'type')
                {{ isset($show->type) ? ucwords($show->type) : 'Unknown' }}

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
                    <button type="submit" class="btn btn-primary">Add and return to Search Results</button>
                  </form>
                  <form action="{{ fullUrl('/anime/add') }}" method="POST">
                    {{ csrf_field() }}
                    <input type="hidden" name="mal_id" value="{{ $show->mal_id }}"></input>
                    <input type="hidden" name="gotodetails" value="1"></input>
                    <button type="submit" class="btn btn-primary">Add and go to Details Page</button>
                  </form>
                @else
                  <p>
                    @if(!isset($show->latest_sub))
                      @if(!$show->videos_initialised)
                        <strong>Latest Subbed:</strong> Searching for Episodes ...
                      @else
                        <strong>Latest Subbed:</strong> No Episodes Available
                      @endif
                    @else
                      <a href="{{ $show->latest_sub->episode_url }}">
                        <strong>Latest Subbed:</strong> Episode {{ $show->latest_sub->episode_num }}
                      </a>
                    </p><p>
                      <strong>Uploaded On:</strong> {{ $show->latest_sub->uploadtime->format('M j, Y (l)') }}
                    @endif
                  </p>
                  <p>
                    @if(!isset($show->latest_dub))
                      @if(!$show->videos_initialised)
                        <strong>Latest Dubbed:</strong> Searching for Episodes ...
                      @else
                        <strong>Latest Dubbed:</strong> No Episodes Available
                      @endif
                    @else
                      <a href="{{ $show->latest_dub->episode_url }}">
                        <strong>Latest Dubbed:</strong> Episode {{ $show->latest_dub->episode_num }}
                      </a>
                    </p><p>
                      <strong>Uploaded On:</strong> {{ $show->latest_dub->uploadtime->format('M j, Y (l)') }}
                    @endif
                  </p>
                @endif

              @endif
            </td>
          @endforeach
        </tr>
      @endforeach
    </tbody>
  </table>
</div>
