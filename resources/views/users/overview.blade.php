@extends('layouts.app')
@section('title', 'Anime Overview')

@section('content-left')
  <div class="content-header">
    Settings
  </div>

  <div class="content-generic">
    <form action="{{ fullUrl('/user/settings/overview') }}" method="POST">
      {{ csrf_field() }}

      <div class="form-group" id="option-states">
        <label for="option-states">Show:</label>
        <div class="checkbox">
          <label>
            <input type="checkbox" name="state_watching" {{ in_array('watching', Auth::user()->viewsettings_overview->get('states')) ? 'checked' : '' }}>
            Currently Watching
          </label>
        </div>
        <div class="checkbox">
          <label>
            <input type="checkbox" name="state_completed" {{ in_array('completed', Auth::user()->viewsettings_overview->get('states')) ? 'checked' : '' }}>
            Completed
          </label>
        </div>
        <div class="checkbox">
          <label>
            <input type="checkbox" name="state_onhold" {{ in_array('onhold', Auth::user()->viewsettings_overview->get('states')) ? 'checked' : '' }}>
            On Hold
          </label>
        </div>
        <div class="checkbox">
          <label>
            <input type="checkbox" name="state_dropped" {{ in_array('dropped', Auth::user()->viewsettings_overview->get('states')) ? 'checked' : '' }}>
            Dropped
          </label>
        </div>
        <div class="checkbox">
          <label>
            <input type="checkbox" name="state_plantowatch" {{ in_array('plantowatch', Auth::user()->viewsettings_overview->get('states')) ? 'checked' : '' }}>
            Plan to Watch
          </label>
        </div>
      </div>

      <div class="form-group">
        <div class="checkbox">
          <label>
            <input type="checkbox" name="option_thumbnails" {{ Auth::user()->viewsettings_overview->get('thumbnails') ? 'checked' : '' }}>
            Show Thumbnails
          </label>
        </div>
      </div>

      <div class="form-group {{ $errors->has('option_cutoff') ? 'has-error' : '' }}">
        <label for="option-cutoff"># of Watchable Episodes to Show:</label>
        <input id="option-cutoff" type="number" class="form-control" name="option_cutoff" value="{{ Auth::user()->viewsettings_overview->get('cutoff') }}" min="1" required>
        @if ($errors->has('option_cutoff'))
          <span class="help-block">
            <strong>{{ $errors->first('option_cutoff') }}</strong>
          </span>
        @endif
      </div>

      <button type="submit" class="btn btn-primary">Set</button>
    </form>
  </div>
@endsection

@section('content-center')
  <div class="content-generic">
    @include('components.anime.table', [
      'shows' => $shows,
      'columns' => $columns,
    ])
    <div class="content-close"></div>
  </div>
@endsection
