Report from AnimeSentinel:
-------------------------------------------------------------------
- {{ $description }} -
<ul>
  @foreach($vars as $key => $value)
    <li>{{ $key }}: {{ $value }}</li>
  @endforeach
</ul>
