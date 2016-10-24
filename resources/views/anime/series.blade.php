@extends('layouts.app')
@section('title', $show->title.' - Series Overview')

@section('content-center')
  <div class="content-header">
    DOT code for this series:
  </div>
  <div class="content-generic">
    <p>
      {!! str_replace('\n', '<br>', $show->seriesDot()) !!}
    </p>
  </div>
  <div class="content-generic">
    <p>
      Copy and paste the above code to <a target="_blank" href="http://sandbox.kidstrythisathome.com/erdos/">this page</a>.
    </p>
    <p>
      TODO: Use google graphs to just put a picture here.
    </p>
  </div>
@endsection
