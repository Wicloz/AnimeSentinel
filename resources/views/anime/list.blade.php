@extends('layouts.app')
@section('title', 'Browse Anime')

@section('content-center')
  <div class="content-header header-center">
    <a href="{{ url('/anime').'?page='.(request()->page - 1) }}" class="arrow-left {{ $left ? '' : 'arrow-hide' }}">&#8666;</a>
    Anime List -
    Page {{ request()->page }}
    <a href="{{ url('/anime').'?page='.(request()->page + 1) }}" class="arrow-right {{ $right ? '' : 'arrow-hide' }}">&#8667;</a>
  </div>

  @if(count($shows) > 0)
    @foreach($shows as $show)
      @if(empty($show->mal))
        @include('components.synopsis', [
          'syn_mal' => false,
          'syn_show' => $show,
          'syn_unique' => $show->id,
        ])
      @else
        @include('components.synopsis', [
          'syn_mal' => true,
          'syn_show' => $show,
          'syn_unique' => $show->id,
        ])
      @endif
    @endforeach
  @else
    <div class="content-header header-center">
      No Anime Found
    </div>
  @endif

  <div class="content-header header-center">
    <a href="{{ url('/anime').'?page='.(request()->page - 1) }}" class="arrow-left {{ $left ? '' : 'arrow-hide' }}">&#8666;</a>
    Anime List -
    Page {{ request()->page }}
    <a href="{{ url('/anime').'?page='.(request()->page + 1) }}" class="arrow-right {{ $right ? '' : 'arrow-hide' }}">&#8667;</a>
  </div>
@endsection
