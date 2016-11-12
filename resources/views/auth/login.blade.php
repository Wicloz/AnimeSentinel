@extends('layouts.app')
@section('title', 'Login')

@section('content-center')
  <div class="content-header">Login</div>
  <div class="content-generic">
    <form class="form-horizontal" role="form" method="POST" action="{{ fullUrl('/login') }}">
      {{ csrf_field() }}

      <div class="form-group {{ $errors->has('email') ? 'has-error' : '' }}">
        <label for="email" class="col-md-4 control-label">E-Mail Address</label>
        <div class="col-md-6">
          <input id="email" type="email" class="form-control" name="email" value="{{ old('email') }}" maxlength="255" required autofocus>
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

      <div class="form-group">
        <div class="col-md-6 col-md-offset-4">
          <div class="checkbox">
            <label>
              <input type="checkbox" name="remember">
              Remember Me
            </label>
          </div>
        </div>
      </div>

      <div class="form-group">
        <div class="col-md-8 col-md-offset-4">
          <button type="submit" class="btn btn-primary">
            Login
          </button>
          <a class="btn btn-link" href="{{ fullUrl('/password/reset') }}">
            Forgot Your Password?
          </a>
        </div>
      </div>

    </form>
    <div class="content-close"></div>
  </div>
@endsection
