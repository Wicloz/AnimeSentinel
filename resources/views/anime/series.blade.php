@extends('layouts.app')
@section('title', $show->title.' - Series Overview')

@section('content-center')
  <div class="content-header">
    DOT code for this series:
  </div>
  <div class="content-generic">
    <pre>{!! str_replace('\n', '<br>', $show->seriesDot()) !!}</pre>
    <div class="content-close"></div>
  </div>
  <div class="content-generic">
    <p>
      Copy and paste the above code to <u><a target="_blank" href="http://www.webgraphviz.com/">this page</a></u>.
    </p>
    <p>
      TODO: Directly put a picture here.
      Preferably with clickable titles.
    </p>
    <div class="content-close"></div>
  </div>
@endsection
