@extends('layouts.app')
@section('title', 'Register')

@section('content-center')
  <div class="content-header">Register</div>
  <div class="content-generic">
    <form class="form-horizontal" role="form" method="POST" action="{{ fullUrl('/register') }}">
      {{ csrf_field() }}

      <div class="form-group">
        <div class="col-md-6 col-md-offset-4">
          <h2 style="display:inline-block;">Account Data:</h2>
          (required)
        </div>
      </div>

      <div class="form-group {{ $errors->has('username') ? 'has-error' : '' }}">
        <label for="username" class="col-md-4 control-label">Username</label>
        <div class="col-md-6">
          <input id="username" type="text" class="form-control" name="username" value="{{ old('username') }}" maxlength="255" required autofocus>
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
          <input id="email" type="email" class="form-control" name="email" value="{{ old('email') }}" maxlength="255" required>
          @if ($errors->has('email'))
            <span class="help-block">
              <strong>{{ $errors->first('email') }}</strong>
            </span>
          @endif
        </div>
      </div>

      <div class="form-group {{ $errors->has('password') ? 'has-error' : '' }}">
        <label for="password" class="col-md-4 control-label">Password</label>
        <div class="col-md-6">
          <input id="password" type="password" class="form-control" name="password" autocomplete="off" required>
          @if ($errors->has('password'))
            <span class="help-block">
              <strong>{{ $errors->first('password') }}</strong>
            </span>
          @endif
        </div>
      </div>

      <div class="form-group {{ $errors->has('password_confirmation') ? 'has-error' : '' }}">
        <label for="password-confirm" class="col-md-4 control-label">Confirm Password</label>
        <div class="col-md-6">
          <input id="password-confirm" type="password" class="form-control" name="password_confirmation" autocomplete="off" required>
          @if ($errors->has('password_confirmation'))
            <span class="help-block">
              <strong>{{ $errors->first('password_confirmation') }}</strong>
            </span>
          @endif
        </div>
      </div>

      <div class="form-group">
        <div class="col-md-6 col-md-offset-4">
          <h2 style="display:inline-block;">MAL Credentials:</h2>
          <a href="{{ fullUrl('/about/mal') }}">(read more)</a>
        </div>
      </div>

      <div class="form-group {{ $errors->has('mal_user') ? 'has-error' : '' }}">
        <label for="mal_user" class="col-md-4 control-label">MAL Username</label>
        <div class="col-md-6">
          <input id="mal_user" type="text" class="form-control" name="mal_user" value="{{ old('mal_user') }}" maxlength="255">
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
          <input id="mal_pass" type="password" class="form-control" name="mal_pass" value="{{ old('mal_pass') }}" maxlength="255">
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
            Register
          </button>
        </div>
      </div>

    </form>
    <div class="content-close"></div>
  </div>
@endsection
