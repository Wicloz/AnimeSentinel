@if (Session::has('alerts_info'))
  @foreach (Session::pull('alerts_info') as $alert)
    <div class="alert {{$alert->type}}">
      {!! $alert->body !!}
    </div>
  @endforeach
@endif

@if (Session::has('alerts_success'))
  @foreach (Session::pull('alerts_success') as $alert)
    <div class="alert {{$alert->type}}">
      {!! $alert->body !!}
    </div>
  @endforeach
@endif

@if (Session::has('alerts_warning'))
  @foreach (Session::pull('alerts_warning') as $alert)
    <div class="alert {{$alert->type}}">
      {!! $alert->body !!}
    </div>
  @endforeach
@endif

@if (Session::has('alerts_error'))
  @foreach (Session::pull('alerts_error') as $alert)
    <div class="alert {{$alert->type}}">
      {!! $alert->body !!}
    </div>
  @endforeach
@endif

@if (count($errors) > 0)
  <div class="alert alert-error">
    <!-- TODO: fancy CSS -->
    <ul>
      @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
      @endforeach
    </ul>
  </div>
@endif
