@if(Auth::user()->mal_user === '')
  <div class="alert alert-warning">
    MAL Interaction Disabled
  </div>
@elseif(!Auth::user()->mal_canread)
  <div class="alert alert-danger">
    MAL Username Invalid
  </div>
@elseif(Auth::user()->mal_pass === '')
  <div class="alert alert-warning">
    MAL Interaction Read Only
  </div>
@elseif(!Auth::user()->mal_canwrite)
  <div class="alert alert-danger">
    MAL Password Invalid
  </div>
@else
  <div class="alert alert-success">
    MAL Credentials Valid
  </div>
@endif
