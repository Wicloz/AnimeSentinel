<div class="synopsis-panel">
  <div class="row">

    <div class="col-sm-2">
      <a {{ $syn_mal ? 'target="_blank"' : '' }} href="{{ $syn_show->details_url }}">
        <img class="img-thumbnail synopsis-thumbnail" src="{{ $syn_show->thumbnail_url }}" alt="{{ $syn_show->title }} - Thumbnail">
      </a>
    </div>

    <div class="col-sm-10">
      <div class="synopsis-title">
        <a {{ $syn_mal ? 'target="_blank"' : '' }} href="{{ $syn_show->details_url }}">{{ $syn_show->title }}</a>
      </div>

      <div class="row">
        <div class="col-sm-9">
          <div class="synopsis-description">
            {!! $syn_show->description !!}
            <!--
            <div class="synopsis-description-top">
              {!! $syn_show->description !!}
            </div>
            -->
            <!--
            <div class="collapsed toggle" data-toggle="collapse" data-target="#description-{{ $syn_unique }}">
              &laquo; Toggle Description &raquo;
            </div>
            <div class="collapse" id="description-{{ $syn_unique }}">
              {!! $syn_show->description !!}
            </div>
            -->
          </div>
        </div>

        <div class="col-sm-3">
          <div class="synopsis-details">
            <p>
              <strong>Status:</strong>
              @if($syn_mal)
                @if(!isset($syn_show->airing_start) || Carbon\Carbon::now()->endOfDay()->lt($syn_show->airing_start))
                  Upcoming
                @elseif(!isset($syn_show->airing_end) || Carbon\Carbon::now()->startOfDay()->lte($syn_show->airing_end))
                  Currently Airing
                @else
                  Completed
                @endif
              @else
                @if(!isset($syn_show->latest_sub->episode_num))
                  Upcoming
                @elseif(!isset($syn_show->episode_amount) || $syn_show->latest_sub->episode_num < $syn_show->episode_amount)
                  Currently Airing
                @else
                  Completed
                @endif
              @endif
            </p>
            <p><strong>Type:</strong> {{ isset($syn_show->type) ? ucwords($syn_show->type) : 'Unknown' }}</p>
            <p><strong>Total Episodes:</strong> {{ $syn_show->episode_amount or 'Unknown' }}</p>
            <p>
              <strong>Duration:</strong>
              @if(isset($syn_show->episode_duration))
                {{ fancyDuration($syn_show->episode_duration * 60, false) }} per ep.
              @else
                Unknown
              @endif
            </p>
            <p>
              <strong>Airing:</strong>
              @if(empty($syn_show->airing_start) && empty($syn_show->airing_end))
                Unknown
              @else
                {{ !empty($syn_show->airing_start) ? $syn_show->airing_start->toFormattedDateString() : '?' }} to {{ !empty($syn_show->airing_end) ? $syn_show->airing_end->toFormattedDateString() : '?' }}
              @endif
            </p>
          </div>
        </div>
      </div>

      @if(!$syn_mal)
        <div class="synopsis-bottombar">
          <div class="row">
            @if(isset($syn_video))
              <div class="col-sm-3">
                <a href="{{ $syn_video->episode_url }}">
                  Episode {{ $syn_video->episode_num }} Has Aired
                </a>
              </div>
              <div class="col-sm-3">
                Episode Type: {{ $syn_video->translation_type === 'sub' ? 'Subbed' : '' }}{{ $syn_video->translation_type === 'dub' ? 'Dubbed' : ''}}
              </div>
              <div class="col-sm-3">
                Uploaded by <a href="{{ $syn_video->streamer->details_url }}">{{ $syn_video->streamer->name }}</a>
              </div>
              <div class="col-sm-3">
                Uploaded on {{ $syn_video->uploadtime->format('M j, Y (l)') }}
              </div>
            @else
              <div class="col-sm-6">
                @if(!isset($syn_show->latest_sub))
                  @if(!$syn_show->videos_initialised)
                    Latest Subbed: Searching for episodes ...
                  @else
                    Latest Subbed: No episodes available
                  @endif
                @else
                  <a href="{{ fullUrl('/anime/'.$syn_show->id.'/sub/episode-'.$syn_show->latest_sub->episode_num) }}">
                    Latest Subbed: Epsiode {{ $syn_show->latest_sub->episode_num }}; Uploaded on {{ $syn_show->latest_sub->uploadtime->format('M j, Y (l)') }}
                  </a>
                @endif
              </div>
              <div class="col-sm-6">
                @if(!isset($syn_show->latest_dub))
                  @if(!$syn_show->videos_initialised)
                    Latest Dubbed: Searching for episodes ...
                  @else
                    Latest Dubbed: No episodes available
                  @endif
                @else
                  <a href="{{ fullUrl('/anime/'.$syn_show->id.'/dub/episode-'.$syn_show->latest_dub->episode_num) }}">
                    Latest Dubbed: Epsiode {{ $syn_show->latest_dub->episode_num }}; Uploaded on {{ $syn_show->latest_dub->uploadtime->format('M j, Y (l)') }}
                  </a>
                @endif
              </div>
            @endif
          </div>
        </div>

      @else
        <div class="synopsis-bottombar">
          <div class="row">
            <div class="col-sm-4">
              This show is not in our database yet.
            </div>
            <div class="col-sm-4">
              <form action="{{ fullUrl('/anime/add') }}" method="POST">
                {{ csrf_field() }}
                <input type="hidden" name="mal_id" value="{{ $syn_show->mal_id }}"></input>
                <input type="hidden" name="gotodetails" value="0"></input>
                <button type="submit" class="btn btn-primary">Add and return to Search Results</button>
              </form>
            </div>
            <div class="col-sm-4">
              <form action="{{ fullUrl('/anime/add') }}" method="POST">
                {{ csrf_field() }}
                <input type="hidden" name="mal_id" value="{{ $syn_show->mal_id }}"></input>
                <input type="hidden" name="gotodetails" value="1"></input>
                <button type="submit" class="btn btn-primary">Add and go to Details Page</button>
              </form>
            </div>
          </div>
        </div>
      @endif
    </div>

  </div>
</div>

<!--
  $syn_mal
  $syn_show
  $syn_unique
  ($syn_video)
-->
