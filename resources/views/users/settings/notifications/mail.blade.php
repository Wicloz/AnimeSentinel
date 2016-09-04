@extends('layouts.app')
@section('title', 'Notification Settings - Mail')

@section('content-center')
  <div class="content-header">MAL Credential Status</div>
  @include('components.malcredstatus')

  <div class="content-header">Mail Notification Settings per Status</div>
  <!--TODO: fancy CSS-->
  <div class="content-generic">
    <form class="form-horizontal" role="form" method="POST" action="{{ fullUrl('/user/notifications/mail/general') }}">
      {{ csrf_field() }}

      <div class="form-group">
        <div class="col-md-6 col-md-offset-4">
          <div class="checkbox">
            <label>
              <input type="checkbox" name="notifications_watching" {{ Auth::user()->nots_mail_settings_general['watching'] ? 'checked' : '' }}>
              Recieve notifications for 'Currently Watching' anime by default.
            </label>
          </div>
        </div>
      </div>

      <div class="form-group">
        <div class="col-md-6 col-md-offset-4">
          <div class="checkbox">
            <label>
              <input type="checkbox" name="notifications_completed" {{ Auth::user()->nots_mail_settings_general['completed'] ? 'checked' : '' }}>
              Recieve notifications for 'Completed' anime by default.
            </label>
          </div>
        </div>
      </div>

      <div class="form-group">
        <div class="col-md-6 col-md-offset-4">
          <div class="checkbox">
            <label>
              <input type="checkbox" name="notifications_onhold" {{ Auth::user()->nots_mail_settings_general['onhold'] ? 'checked' : '' }}>
              Recieve notifications for 'On Hold' anime by default.
            </label>
          </div>
        </div>
      </div>

      <div class="form-group">
        <div class="col-md-6 col-md-offset-4">
          <div class="checkbox">
            <label>
              <input type="checkbox" name="notifications_dropped" {{ Auth::user()->nots_mail_settings_general['dropped'] ? 'checked' : '' }}>
              Recieve notifications for 'Dropped' anime by default.
            </label>
          </div>
        </div>
      </div>

      <div class="form-group">
        <div class="col-md-6 col-md-offset-4">
          <div class="checkbox">
            <label>
              <input type="checkbox" name="notifications_ptw" {{ Auth::user()->nots_mail_settings_general['ptw'] ? 'checked' : '' }}>
              Recieve notifications for 'Plan to Watch' anime by default.
            </label>
          </div>
        </div>
      </div>

      <div class="form-group">
        <div class="col-md-6 col-md-offset-4">
          <button type="submit" class="btn btn-primary">
            Update
          </button>
        </div>
      </div>

    </form>
    <div class="content-close"></div>
  </div>

  <div class="content-header">Mail Notification Settings per Anime</div>
  <div class="content-generic">
    <!--TODO: CSS and active urls-->
    <div class="row">
      <div class="col-md-2 col-md-offset-1">
        <a href="{{ fullUrl('/user/notifications/mail').'?status=watching' }}" class="align-center"><h2>Watching</h2></a>
      </div>
      <div class="col-md-2">
        <a href="{{ fullUrl('/user/notifications/mail').'?status=completed' }}" class="align-center"><h2>Completed</h2></a>
      </div>
      <div class="col-md-2">
        <a href="{{ fullUrl('/user/notifications/mail').'?status=onhold' }}" class="align-center"><h2>On Hold</h2></a>
      </div>
      <div class="col-md-2">
        <a href="{{ fullUrl('/user/notifications/mail').'?status=dropped' }}" class="align-center"><h2>Dropped</h2></a>
      </div>
      <div class="col-md-2">
        <a href="{{ fullUrl('/user/notifications/mail').'?status=ptw' }}" class="align-center"><h2>Plan to Watch</h2></a>
      </div>
    </div>
    <div class="content-close"></div>
  </div>

  <!--TODO: fancy CSS-->
  @if(!empty($mal_list))
    <div class="content-generic">
      <form class="form-horizontal" role="form" method="POST" action="{{ fullUrl('/user/notifications/mail/specific') }}">
        {{ csrf_field() }}
        <input type="hidden" name="status" value="{{ $loadedStatus }}">

        @foreach($mal_list as $mal_show)
          <div class="form-group">
            <div class="col-md-6 col-md-offset-4">
              <h3>{{ $mal_show->title }}</h3>
            </div>
          </div>

          <div class="form-group">
            <div class="col-md-6 col-md-offset-4">
              <div class="radio">
                <label>
                  <input type="radio" name="state_{{ $mal_show->mal_id }}" value="" {{ !array_key_exists($mal_show->mal_id, Auth::user()->nots_mail_settings_specific[$loadedStatus]) ? 'checked' : '' }}>
                  Use default settings
                </label>
              </div>
              <div class="radio">
                <label>
                  <input type="radio" name="state_{{ $mal_show->mal_id }}" value="1" {{ array_key_exists($mal_show->mal_id, Auth::user()->nots_mail_settings_specific[$loadedStatus]) && Auth::user()->nots_mail_settings_specific[$loadedStatus][$mal_show->mal_id] ? 'checked' : '' }}>
                  Always send notifications
                </label>
              </div>
              <div class="radio">
                <label>
                  <input type="radio" name="state_{{ $mal_show->mal_id }}" value="0" {{ array_key_exists($mal_show->mal_id, Auth::user()->nots_mail_settings_specific[$loadedStatus]) && !Auth::user()->nots_mail_settings_specific[$loadedStatus][$mal_show->mal_id] ? 'checked' : '' }}>
                  Never send notifications
                </label>
              </div>
            </div>
          </div>
        @endforeach

        <div class="form-group">
          <div class="col-md-6 col-md-offset-4">
            <button type="submit" class="btn btn-primary">
              Update
            </button>
          </div>
        </div>

      </form>
      <div class="content-close"></div>
    @endif
  </div>
@endsection
