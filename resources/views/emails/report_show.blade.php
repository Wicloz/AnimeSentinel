Report from AnimeSentinel:
-------------------------------------------------------------------
Show:
<ul>
  <li>Show Title: {{ $show->title }}</li>
  <li>Show Id: {{ $show->id }}</li>
  <li>Show MAL Id: {{ $show->mal_id or 'NA' }}</li>
  <li>Show Link: <a>{{ $show->details_url_static }}</a></li>
</ul>
-------------------------------------------------------------------
Report:
- {{ $description }} -
<ul>
  @foreach($vars as $key => $value)
    <li>{{ $key }}: {{ $value }}</li>
  @endforeach
</ul>
