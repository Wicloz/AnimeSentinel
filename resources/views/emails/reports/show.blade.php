Report from AnimeSentinel ({{ config('app.env') }}):<br>
-------------------------------------------------------------------
<br>
Show:
<ul>
  <li>Show Title: {{ $show->title }}</li>
  <li>Show Id: {{ $show->id }}</li>
  <li>Show MAL Id: {{ $show->mal_id or 'NA' }}</li>
  <li>Show Link: <a href="{{ $show->details_url }}">{{ $show->details_url }}</a></li>
</ul>
-------------------------------------------------------------------
<br>
Report:
<p>
  - {{ $description }} -
</p>
<ul>
  @foreach($vars as $key => $value)
    <li>{{ $key }}: {!! nl2br($value) !!}</li>
  @endforeach
</ul>
