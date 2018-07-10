@extends('layouts.default')

@section('title', 'Trades')

@section('content')
	
<h2>@if($trade['parameters']['type'] == 'Short')<i style="transform: rotate(180deg) scaleX(-1);" class="text-danger fas fa-chart-line"></i> {{$trade['parameters']['coin']}} Short @endif
						@if($trade['parameters']['type'] == 'Long')<i class="text-success fas fa-chart-line"></i> {{$trade['parameters']['coin']}} Long @endif @ {{$trade['price']}} <span style="float: right;">{{substr($trade['parameters']['datetime'], 0, -3)}}</span></h2>
<form method="post" action="/trades/update/{{$trade['bitfinex_id']}}">
{{csrf_field()}}
<div class="form-group">
  <label for="comment">Comment:</label>
  <textarea class="form-control" rows="5" name="comment" id="comment">{{$trade['comment']}}</textarea>
  <input type="hidden" name="previous_url" value="{{\URL::previous()}}" />
  <br />
  <input type="submit" class="btn btn-primary" value="Save">
</div>
</form>

@endsection