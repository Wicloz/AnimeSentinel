@extends('layouts.app')
@section('title', 'Reset Password')

@section('content-center')
  <div class="content-header">Reset Password</div>
  <div class="content-generic">
    @if (session('status'))
      <div class="alert alert-success">
        {{ session('status') }}
      </div>
    @endif

    <form class="form-horizontal" role="form" method="POST" action="{{ fullUrl('/password/email') }}">
      {{ csrf_field() }}

      <div class="form-group {{ $errors->has('email') ? 'has-error' : '' }}">
        <label for="email" class="col-md-4 control-label">E-Mail Address</label>
        <div class="col-md-6">
          <input id="email" type="email" class="form-control" name="email" value="{{ old('email') }}">
          @if ($errors->has('email'))
            <span class="help-block">
              <strong>{{ $errors->first('email') }}</strong>
            </span>
          @endif
        </div>
      </div>

      <div class="form-group">
        <div class="col-md-6 col-md-offset-4">
          <button type="submit" class="btn btn-primary">
            Send Password Reset Link
          </button>
        </div>
      </div>

    </form>
    <div class="content-close"></div>
  </div>
@endsection
