@extends('layouts.app')
@section('title', 'General Settings')

@section('content-center')
  <div class="content-header">Change Account Setttings</div>
  <div class="content-generic">
    <form class="form-horizontal" role="form" method="POST" action="{{ fullUrl('/user/settings/general') }}">
      {{ csrf_field() }}

      <div class="form-group">
        <div class="col-md-6 col-md-offset-4">
          <h2 style="display:inline-block;">Account Data:</h2>
        </div>
      </div>

      <div class="form-group {{ $errors->has('username') ? 'has-error' : '' }}">
        <label for="username" class="col-md-4 control-label">Username</label>
        <div class="col-md-6">
          <input id="username" type="text" class="form-control" name="username" value="{{ Auth::user()->username }}" maxlength="255">
          @if ($errors->has('username'))
            <span class="help-block">
              <strong>{{ $errors->first('username') }}</strong>
            </span>
          @endif
        </div>
      </div>

      <div class="form-group {{ $errors->has('email') ? 'has-error' : '' }}">
        <label for="email" class="col-md-4 control-label">E-Mail Address</label>
        <div class="col-md-6">
          <input id="email" type="email" class="form-control" name="email" value="{{ Auth::user()->email }}" maxlength="255">
          @if ($errors->has('email'))
            <span class="help-block">
              <strong>{{ $errors->first('email') }}</strong>
            </span>
          @endif
        </div>
      </div>

      <div class="form-group">
        <div class="col-md-6 col-md-offset-4">
          <h2 style="display:inline-block;">Notification Settings:</h2>
        </div>
      </div>

      <div class="form-group">
        <div class="col-md-6 col-md-offset-4">
          <div class="checkbox">
            <label>
              <input type="checkbox" name="nots_mail_state" {{ Auth::user()->nots_mail_state ? 'checked' : '' }}>
              Enable Mail Notifications
            </label>
          </div>
        </div>
      </div>

      <div class="form-group">
        <div class="col-md-6 col-md-offset-4">
          <div class="checkbox">
            <label>
              <input type="checkbox" name="auto_watching_state" {{ Auth::user()->auto_watching_state ? 'checked' : '' }}>
              Automatically mark anime that you want to recieve notifications for as 'Currently Watching' when they starts airing.
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

  <div class="content-header">Change MAL Credentials &ndash; <a href="{{ fullUrl('/about/mal') }}">(read more)</a></div>
  <div class="content-generic">
    <form class="form-horizontal" role="form" method="POST" action="{{ fullUrl('/user/settings/mal') }}">
      {{ csrf_field() }}

      <div class="form-group">
        <div class="col-md-6 col-md-offset-4">
          @include('components.mal.credstatus')
        </div>
      </div>

      <div class="form-group {{ $errors->has('mal_user') ? 'has-error' : '' }}">
        <label for="mal_user" class="col-md-4 control-label">MAL Username</label>
        <div class="col-md-6">
          <input id="mal_user" type="text" class="form-control" name="mal_user" value="{{ Auth::user()->mal_user }}" maxlength="255">
          @if ($errors->has('mal_user'))
            <span class="help-block">
              <strong>{{ $errors->first('mal_user') }}</strong>
            </span>
          @endif
        </div>
      </div>

      <div class="form-group {{ $errors->has('mal_pass') ? 'has-error' : '' }}">
        <label for="mal_pass" class="col-md-4 control-label">MAL Password</label>
        <div class="col-md-6">
          <input id="mal_pass" type="password" class="form-control" name="mal_pass" autocomplete="off" maxlength="255">
          @if ($errors->has('mal_pass'))
            <span class="help-block">
              <strong>{{ $errors->first('mal_pass') }}</strong>
            </span>
          @endif
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

  <div class="content-header">Change Password</div>
  <div class="content-generic">
    <form class="form-horizontal" role="form" method="POST" action="{{ fullUrl('/user/settings/password') }}">
      {{ csrf_field() }}

      <div class="form-group {{ $errors->has('current_password') ? 'has-error' : '' }}">
        <label for="current_password" class="col-md-4 control-label">Current Password</label>
        <div class="col-md-6">
          <input id="current_password" type="password" class="form-control" name="current_password" autocomplete="off" required>
          @if ($errors->has('current_password'))
            <span class="help-block">
              <strong>{{ $errors->first('current_password') }}</strong>
            </span>
          @endif
        </div>
      </div>

      <div class="form-group {{ $errors->has('new_password') ? 'has-error' : '' }}">
        <label for="new_password" class="col-md-4 control-label">New Password</label>
        <div class="col-md-6">
          <input id="new_password" type="password" class="form-control" name="new_password" autocomplete="off" required>
          @if ($errors->has('new_password'))
            <span class="help-block">
              <strong>{{ $errors->first('new_password') }}</strong>
            </span>
          @endif
        </div>
      </div>

      <div class="form-group {{ $errors->has('new_password_confirmation') ? 'has-error' : '' }}">
        <label for="new_password-confirm" class="col-md-4 control-label">Confirm New Password</label>
        <div class="col-md-6">
          <input id="new_password-confirm" type="password" class="form-control" name="new_password_confirmation" autocomplete="off" required>
          @if ($errors->has('new_password_confirmation'))
            <span class="help-block">
              <strong>{{ $errors->first('new_password_confirmation') }}</strong>
            </span>
          @endif
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
@endsection
