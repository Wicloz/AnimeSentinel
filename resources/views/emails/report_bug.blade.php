Report from AnimeSentinel:<br>
-------------------------------------------------------------------
<br>
- {{ $description }} -
<ul>
  @foreach($vars as $key => $value)
    <li>{{ $key }}: {{ $value }}</li>
  @endforeach
</ul>
