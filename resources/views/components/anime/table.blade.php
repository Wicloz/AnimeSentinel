<div class="table-responsive">
  <table class="table table-striped table-anime">

    <thead>
       <tr>
         @foreach ($columns as $column)
           <th class="col-{{ $column }}">
             @if ($column === 'thumbnail')
             @elseif ($column === 'title')
               Title
             @endif
           </th>
         @endforeach
       </tr>
    </thead>

    <tbody>
      @foreach ($shows as $show)
        <tr>
          @foreach ($columns as $column)
            <td class="col-{{ $column }}">
              @if ($column === 'thumbnail')
                <a {{ $show->mal ? 'target="_blank"' : '' }} href="{{ $show->details_url }}">
                  <img class="img-thumbnail" src="{{ $show->thumbnail_url }}" alt="{{ $show->title }} - Thumbnail">
                </a>

              @elseif ($column === 'title')
                <a {{ $show->mal ? 'target="_blank"' : '' }} href="{{ $show->details_url }}">
                  {{ $show->title }}
                </a>

              @endif
            </td>
          @endforeach
        </tr>
      @endforeach
    </tbody>
  </table>
</div>
