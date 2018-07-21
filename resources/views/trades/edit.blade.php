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
  <div class="row">
    <div class="col-md-6">
      <div class="card">
        <div class="card-header bg-danger text-white">
          Fail reasons
        </div>
        <ul class="list-group list-group-flush" id="reason-fail">
          @foreach ($reasons as $reason)
            @if ($reason['type'] == 'fail')
              <li class="list-group-item">
                    <input type="hidden" name="reason_{{$reason->id}}" value="0" />
                    <input type="checkbox" name="reason_{{$reason->id}}" @if (DB::table('reasons_trades')->where('reason_id', $reason->id)->where('bitfinex_id', $bitfinex_id)->get()->count()) checked @endif value="1" /> {{$reason['reason']}}
                </li>
            @endif
          @endforeach
          <li class="list-group-item" id="add-reason-fail-li"><a style="color: #007bff; cursor: pointer;" id="add-reason-fail">Add</a></li>
        </ul>
      </div>
    </div>
    <div class="col-md-6">
        <div class="card">
          <div class="card-header bg-success text-white">
            Success reasons
          </div>
          <ul class="list-group list-group-flush" id="reason-success">
            @foreach ($reasons as $reason)
              @if ($reason['type'] == 'success')
                <li class="list-group-item">
                    <input type="hidden" name="reason_{{$reason->id}}" value="0" />
                    <input type="checkbox" name="reason_{{$reason->id}}" @if (DB::table('reasons_trades')->where('reason_id', $reason->id)->where('bitfinex_id', $bitfinex_id)->get()->count()) checked @endif value="1" /> {{$reason['reason']}}
                </li>
              @endif
            @endforeach
            <li class="list-group-item" id="add-reason-success-li"><a style="color: #007bff; cursor: pointer;" id="add-reason-success">Add</a></li>
          </ul>
        </div>
    </div>
  </div>

  <br />

  <div class="card">
    <div class="card-body">

      <input type="hidden" name="resolved" value="0" />
      <input type="checkbox" @if ($trade['resolved']) checked @endif id="resolved" name="resolved" value="1" />
      <label for="resolved">Resolved</label>
      <input type="hidden" name="previous_url" value="{{\URL::previous()}}" />

      <br /><br />

      <input type="submit" class="btn btn-primary" value="Save">
    </div>
  </div>
</div>
</form>

@endsection