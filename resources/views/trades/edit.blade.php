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
  <br />
  <h2>Indicators</h2>
  
  <div id="indicators" class="row">

    @foreach ($indicators as $key => $indicator)
      <div class="col-md-3">
        <div class="input-group mb-3">
          <div class="input-group-prepend">
            <label style="width: 115px;" class="input-group-text" for="inputGroupSelect01">{{$indicator_names[$key]}}</label>
          </div>
          <select name="{{$key}}" class="custom-select" id="inputGroupSelect01">
            <option value="null" selected>-</option>
            @foreach ($indicator as $key2 => $value)
              <option @if ($trade[$key] == $key2) selected @endif value="{{$key2}}">{{$value}}</option>
            @endforeach
          </select>
        </div>
      </div>
    @endforeach 

  </div>

  <br />
  <label for="resolved">Resolved:</label>
  <input type="hidden" name="resolved" value="0" />
  <input type="checkbox" @if ($trade['resolved']) checked @endif id="resolved" name="resolved" value="1" />
  <input type="hidden" name="previous_url" value="{{\URL::previous()}}" />
  <br />
  <input type="submit" class="btn btn-primary" value="Save">
</div>
</form>

@endsection