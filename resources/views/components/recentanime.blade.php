<div class="content-header">Recently Uploaded</div>
@foreach($recent as $video)
  <div class="synopsis-panel">
    <div class="row">
      <div class="col-sm-2">
        <a href="{{ $video->show->details_url }}">
          <img class="img-thumbnail synopsis-thumbnail" src="{{ $video->show->thumbnail_url }}" alt="{{ $video->show->title }} - Thumbnail">
        </a>
      </div>
      <div class="col-sm-10">
        <div class="synopsis-title"><a href="{{ $video->show->details_url }}">{{ $video->show->title }}</a></div>
        <div class="synopsis-details">
          <div class="collapsed toggle" data-toggle="collapse" data-target="#description-{{ $video->show->id }}-{{ $video->translation_type }}-{{ $video->episode_num }}">
            &laquo; Toggle Description &raquo;
          </div>
          <div class="collapse" id="description-{{ $video->show->id }}-{{ $video->translation_type }}-{{ $video->episode_num }}">
            {!! $video->show->description !!}
          </div>
        </div>
        <div class="synopsis-episodes">
          <div class="row">
            <div class="col-sm-4">
              <a href="{{ url("/anime/$video->show->id/$video->translation_type/episode-$video->episode_num") }}">
                Episode {{ $video->episode_num }} Has Aired
              </a>
            </div>
            <div class="col-sm-4">
              Uploaded Episode Type: {{ $video->translation_type === 'sub' ? 'Subbed' : '' }} {{ $video->translation_type === 'dub' ? 'Dubbed' : ''}}
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
@endforeach
