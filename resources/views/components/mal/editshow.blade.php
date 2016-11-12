<div class="content-generic">
  <form action="{{ !isset($show->mal_show) ? fullUrl('/user/setmal/add') : fullUrl('/user/setmal/full') }}" method="POST">
    {{ csrf_field() }}
    <input type="hidden" name="show_id" value="{{ $show->id }}">

    <div class="form-group">
      <label for="status">Status:</label>
      <select class="form-control" id="status" name="status">
        <option value="watching" {{ !isset($show->mal_show) || $show->mal_show->status === 'watching' ? 'selected' : '' }}>Watching</option>
        <option value="completed" {{ isset($show->mal_show) && $show->mal_show->status === 'completed' ? 'selected' : '' }}>Completed</option>
        <option value="onhold" {{ isset($show->mal_show) && $show->mal_show->status === 'onhold' ? 'selected' : '' }}>On Hold</option>
        <option value="dropped" {{ isset($show->mal_show) && $show->mal_show->status === 'dropped' ? 'selected' : '' }}>Dropped</option>
        <option value="plantowatch" {{ isset($show->mal_show) && $show->mal_show->status === 'plantowatch' ? 'selected' : '' }}>Plan to Watch</option>
      </select>
    </div>

    <div class="form-group {{ $errors->has('eps_watched') ? 'has-error' : '' }}">
      <label for="eps_watched">Episodes Watched:</label>
      <div class="input-group">
        <input type="number" class="form-control" id="eps_watched" name="eps_watched" value="{{ $errors->has('eps_watched') ? old('eps_watched') : (isset($show->mal_show) ? $show->mal_show->eps_watched : (isset($video) ? $video->episode_num : 0)) }}" min="0" max="{{ $show->episode_amount or '2000' }}" required>
        <div class="input-group-addon">/ {{ $show->episode_amount or '?' }}</div>
      </div>
      @if ($errors->has('eps_watched'))
        <span class="help-block">
          <strong>{{ $errors->first('eps_watched') }}</strong>
        </span>
      @endif
    </div>

    <div class="form-group">
      <label for="score">Your Score:</label>
      <select class="form-control" id="score" name="score">
        <option value="0" {{ !isset($show->mal_show) || $show->mal_show->score === 0 ? 'selected' : '' }}>No Score</option>
        <option value="10" {{ isset($show->mal_show) && $show->mal_show->score === 10 ? 'selected' : '' }}>(10) Masterpiece</option>
        <option value="9" {{ isset($show->mal_show) && $show->mal_show->score === 9 ? 'selected' : '' }}>(9) Great</option>
        <option value="8" {{ isset($show->mal_show) && $show->mal_show->score === 8 ? 'selected' : '' }}>(8) Very Good</option>
        <option value="7" {{ isset($show->mal_show) && $show->mal_show->score === 7 ? 'selected' : '' }}>(7) Good</option>
        <option value="6" {{ isset($show->mal_show) && $show->mal_show->score === 6 ? 'selected' : '' }}>(6) Fine</option>
        <option value="5" {{ isset($show->mal_show) && $show->mal_show->score === 5 ? 'selected' : '' }}>(5) Average</option>
        <option value="4" {{ isset($show->mal_show) && $show->mal_show->score === 4 ? 'selected' : '' }}>(4) Bad</option>
        <option value="3" {{ isset($show->mal_show) && $show->mal_show->score === 3 ? 'selected' : '' }}>(3) Very Bad</option>
        <option value="2" {{ isset($show->mal_show) && $show->mal_show->score === 2 ? 'selected' : '' }}>(2) Horrible</option>
        <option value="1" {{ isset($show->mal_show) && $show->mal_show->score === 1 ? 'selected' : '' }}>(1) Appalling</option>
      </select>
    </div>

    <button type="submit" class="btn btn-default btn-block">{{ !isset($show->mal_show) ? 'Add to My List' : 'Update' }}</button>
  </form>
</div>
