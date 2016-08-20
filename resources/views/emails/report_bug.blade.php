Report from AnimeSentinel:<br>
-------------------------------------------------------------------
<p>
  - {{ $description }} -
</p>
<ul>
  @foreach($vars as $key => $value)
    <li>{{ $key }}: {{ $value }}</li>
  @endforeach
</ul>
