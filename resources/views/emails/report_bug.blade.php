Report from AnimeSentinel ({{ config('app.env') }}):<br>
-------------------------------------------------------------------
<p>
  - {{ $description }} -
</p>
<ul>
  @foreach($vars as $key => $value)
    <li>{{ $key }}: {!! nl2br($value) !!}</li>
  @endforeach
</ul>
