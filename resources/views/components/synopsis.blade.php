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

      <div class="synopsis-details">
        @if(!$syn_mal)
          <div class="collapsed toggle" data-toggle="collapse" data-target="#description-{{ $syn_unique }}">
            &laquo; Toggle Description &raquo;
          </div>
          <div class="collapse" id="description-{{ $syn_unique }}">
            {!! $syn_show->description !!}
          </div>
        @else
          <p>This show is not in our database yet.</p>
          <form action="{{ url('/anime/add') }}" method="POST">
            {{ csrf_field() }}
            <input type="hidden" name="title" value="{{ $syn_show->title }}"></input>
            <button type="submit" class="btn btn-primary">Click To Add</button>
          </form>
        @endif
      </div>

      @if(!$syn_mal)
        <div class="synopsis-episodes">
          <div class="row">
            @if(!empty($syn_video))
              <div class="col-sm-6">
                <a href="{{ $syn_video->episode_url }}">
                  Episode {{ $syn_video->episode_num }} Has Aired
                </a>
              </div>
              <div class="col-sm-6">
                Uploaded Episode Type: {{ $syn_video->translation_type === 'sub' ? 'Subbed' : '' }}{{ $syn_video->translation_type === 'dub' ? 'Dubbed' : ''}}
              </div>

            @else

              <div class="col-sm-6">
                @if(isset($syn_show->latest_sub))
                  <a href="{{ url("/anime/$syn_show->id/sub/episode-$syn_show->latest_sub") }}">
                    Latest Subbed: Epsiode {{ $syn_show->latest_sub }}
                  </a>
                @else
                  Latest Subbed: No episodes available
                @endif
              </div>
              <div class="col-sm-6">
                @if(isset($syn_show->latest_dub))
                  <a href="{{ url("/anime/$syn_show->id/dub/episode-$syn_show->latest_dub") }}">
                    Latest Dubbed: Epsiode {{ $syn_show->latest_dub }}
                  </a>
                @else
                  Latest Dubbed: No episodes available
                @endif
              </div>
            @endif
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
