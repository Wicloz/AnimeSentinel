@extends('layouts.app')
@section('title', 'Notification Settings - Mail')

@section('content-center')
  <div class="content-header">MAL Credential Status</div>
  @include('components.mal.credstatus')

  <div class="content-header">Mail Notification Settings per Status</div>
  <!--TODO: fancy CSS-->
  <div class="content-generic">
    <form class="form-horizontal" role="form" method="POST" action="{{ fullUrl('/user/notifications/mail/general') }}">
      {{ csrf_field() }}

      <div class="form-group">
        <div class="col-md-6 col-md-offset-4">
          <h3>Default setting for 'Currently Watching' anime:</h3>
        </div>
      </div>

      <div class="form-group">
        <div class="col-md-6 col-md-offset-4">
          <div class="radio">
            <label>
              <input type="radio" name="setting_watching" value="both" {{ Auth::user()->nots_mail_settings['watching'] === 'both' ? 'checked' : '' }}>
              Always send notifications
            </label>
          </div>
          <div class="radio">
            <label>
              <input type="radio" name="setting_watching" value="none" {{ Auth::user()->nots_mail_settings['watching'] === 'none' ? 'checked' : '' }}>
              Never send notifications
            </label>
          </div>
          <div class="radio">
            <label>
              <input type="radio" name="setting_watching" value="sub" {{ Auth::user()->nots_mail_settings['watching'] === 'sub' ? 'checked' : '' }}>
              Only send notifications for Subs
            </label>
          </div>
          <div class="radio">
            <label>
              <input type="radio" name="setting_watching" value="dub" {{ Auth::user()->nots_mail_settings['watching'] === 'dub' ? 'checked' : '' }}>
              Only send notifications for Dubs
            </label>
          </div>
        </div>
      </div>

      <div class="form-group">
        <div class="col-md-6 col-md-offset-4">
          <h3>Default setting for 'Completed' anime:</h3>
        </div>
      </div>

      <div class="form-group">
        <div class="col-md-6 col-md-offset-4">
          <div class="radio">
            <label>
              <input type="radio" name="setting_completed" value="both" {{ Auth::user()->nots_mail_settings['completed'] === 'both' ? 'checked' : '' }}>
              Always send notifications
            </label>
          </div>
          <div class="radio">
            <label>
              <input type="radio" name="setting_completed" value="none" {{ Auth::user()->nots_mail_settings['completed'] === 'none' ? 'checked' : '' }}>
              Never send notifications
            </label>
          </div>
          <div class="radio">
            <label>
              <input type="radio" name="setting_completed" value="sub" {{ Auth::user()->nots_mail_settings['completed'] === 'sub' ? 'checked' : '' }}>
              Only send notifications for Subs
            </label>
          </div>
          <div class="radio">
            <label>
              <input type="radio" name="setting_completed" value="dub" {{ Auth::user()->nots_mail_settings['completed'] === 'dub' ? 'checked' : '' }}>
              Only send notifications for Dubs
            </label>
          </div>
        </div>
      </div>

      <div class="form-group">
        <div class="col-md-6 col-md-offset-4">
          <h3>Default setting for 'On Hold' anime:</h3>
        </div>
      </div>

      <div class="form-group">
        <div class="col-md-6 col-md-offset-4">
          <div class="radio">
            <label>
              <input type="radio" name="setting_onhold" value="both" {{ Auth::user()->nots_mail_settings['onhold'] === 'both' ? 'checked' : '' }}>
              Always send notifications
            </label>
          </div>
          <div class="radio">
            <label>
              <input type="radio" name="setting_onhold" value="none" {{ Auth::user()->nots_mail_settings['onhold'] === 'none' ? 'checked' : '' }}>
              Never send notifications
            </label>
          </div>
          <div class="radio">
            <label>
              <input type="radio" name="setting_onhold" value="sub" {{ Auth::user()->nots_mail_settings['onhold'] === 'sub' ? 'checked' : '' }}>
              Only send notifications for Subs
            </label>
          </div>
          <div class="radio">
            <label>
              <input type="radio" name="setting_onhold" value="dub" {{ Auth::user()->nots_mail_settings['onhold'] === 'dub' ? 'checked' : '' }}>
              Only send notifications for Dubs
            </label>
          </div>
        </div>
      </div>

      <div class="form-group">
        <div class="col-md-6 col-md-offset-4">
          <h3>Default setting for 'Dropped' anime:</h3>
        </div>
      </div>

      <div class="form-group">
        <div class="col-md-6 col-md-offset-4">
          <div class="radio">
            <label>
              <input type="radio" name="setting_dropped" value="both" {{ Auth::user()->nots_mail_settings['dropped'] === 'both' ? 'checked' : '' }}>
              Always send notifications
            </label>
          </div>
          <div class="radio">
            <label>
              <input type="radio" name="setting_dropped" value="none" {{ Auth::user()->nots_mail_settings['dropped'] === 'none' ? 'checked' : '' }}>
              Never send notifications
            </label>
          </div>
          <div class="radio">
            <label>
              <input type="radio" name="setting_dropped" value="sub" {{ Auth::user()->nots_mail_settings['dropped'] === 'sub' ? 'checked' : '' }}>
              Only send notifications for Subs
            </label>
          </div>
          <div class="radio">
            <label>
              <input type="radio" name="setting_dropped" value="dub" {{ Auth::user()->nots_mail_settings['dropped'] === 'dub' ? 'checked' : '' }}>
              Only send notifications for Dubs
            </label>
          </div>
        </div>
      </div>

      <div class="form-group">
        <div class="col-md-6 col-md-offset-4">
          <h3>Default setting for 'Plan to Watch' anime:</h3>
        </div>
      </div>

      <div class="form-group">
        <div class="col-md-6 col-md-offset-4">
          <div class="radio">
            <label>
              <input type="radio" name="setting_plantowatch" value="both" {{ Auth::user()->nots_mail_settings['plantowatch'] === 'both' ? 'checked' : '' }}>
              Always send notifications
            </label>
          </div>
          <div class="radio">
            <label>
              <input type="radio" name="setting_plantowatch" value="none" {{ Auth::user()->nots_mail_settings['plantowatch'] === 'none' ? 'checked' : '' }}>
              Never send notifications
            </label>
          </div>
          <div class="radio">
            <label>
              <input type="radio" name="setting_plantowatch" value="sub" {{ Auth::user()->nots_mail_settings['plantowatch'] === 'sub' ? 'checked' : '' }}>
              Only send notifications for Subs
            </label>
          </div>
          <div class="radio">
            <label>
              <input type="radio" name="setting_plantowatch" value="dub" {{ Auth::user()->nots_mail_settings['plantowatch'] === 'dub' ? 'checked' : '' }}>
              Only send notifications for Dubs
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
        <a href="{{ fullUrl('/user/notifications/mail').'?status=plantowatch' }}" class="align-center"><h2>Plan to Watch</h2></a>
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
                  <input type="radio" name="setting_{{ $mal_show->mal_id }}" value="null" {{ Auth::user()->malFields()->where('mal_id', $mal_show->mal_id)->first()->nots_mail_setting === null ? 'checked' : '' }}>
                  Use default settings
                </label>
              </div>
              <div class="radio">
                <label>
                  <input type="radio" name="setting_{{ $mal_show->mal_id }}" value="both" {{ Auth::user()->malFields()->where('mal_id', $mal_show->mal_id)->first()->nots_mail_setting === 'both' ? 'checked' : '' }}>
                  Always send notifications
                </label>
              </div>
              <div class="radio">
                <label>
                  <input type="radio" name="setting_{{ $mal_show->mal_id }}" value="none" {{ Auth::user()->malFields()->where('mal_id', $mal_show->mal_id)->first()->nots_mail_setting === 'none' ? 'checked' : '' }}>
                  Never send notifications
                </label>
              </div>
              <div class="radio">
                <label>
                  <input type="radio" name="setting_{{ $mal_show->mal_id }}" value="sub" {{ Auth::user()->malFields()->where('mal_id', $mal_show->mal_id)->first()->nots_mail_setting === 'sub' ? 'checked' : '' }}>
                  Only send notifications for Subs
                </label>
              </div>
              <div class="radio">
                <label>
                  <input type="radio" name="setting_{{ $mal_show->mal_id }}" value="dub" {{ Auth::user()->malFields()->where('mal_id', $mal_show->mal_id)->first()->nots_mail_setting === 'dub' ? 'checked' : '' }}>
                  Only send notifications for Dubs
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
