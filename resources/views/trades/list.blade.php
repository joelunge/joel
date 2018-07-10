@extends('layouts.default')

@section('title', 'Trades')

@section('content')
	<ul class="nav nav-pills">
      <li style="margin-right: 15px;">
      	<a @if($_GET['show'] == "10_trades") class="text-muted" @endif href="?show=10_trades">10 trades </a>
      </li>
      <li style="margin-right: 15px;">
      	<a @if($_GET['show'] == "7_days") class="text-muted" @endif href="?show=7_days">7 days </a>
      </li>
      <li style="margin-right: 15px;">
      	<a @if($_GET['show'] == "30_days") class="text-muted" @endif href="?show=30_days">30 days </a>
      </li>
      <li style="margin-right: 15px;">
      	<a @if($_GET['show'] == "3_months") class="text-muted" @endif href="?show=3_months">3 months </a>
      </li>
      <li style="margin-right: 15px;">
      	<a @if($_GET['show'] == "all") class="text-muted" @endif href="?show=all">All </a>
      </li>
    </ul>
	<hr />
	<table class="table table-dark">
		<thead>
			<tr>
				<td>Date</td>
				<td>Coin</td>
				<td>Result</td>
				<td>Fees</td>
				<td>Duration</td>
				<td>Balance</td>
				<td></td>
			</tr>
		</thead>
		<tbody>
			@foreach ($trades as $key => $trade)
				<tr id="{{$trade['parameters']['bitfinex_id']}}">
					<td>{{(new DateTime($trade['parameters']['date']))->format('d M')}} <small>{{substr($trade['parameters']['datetime'], 11, 5)}}</small></td>
					<td>
						@if($trade['parameters']['type'] == 'Short')
							<i style="transform: rotate(180deg) scaleX(-1);" class="{!! (Auth::id() == 1) ? 'text-danger': '' !!}
 fas fa-chart-line"></i> {{$trade['parameters']['coin']}}
						@endif
						
						@if($trade['parameters']['type'] == 'Long')
							<i class="{!! (Auth::id() == 1) ? 'text-success': '' !!} fas fa-chart-line"></i> 
							{{$trade['parameters']['coin']}}
						@endif
					</td>
					<td><span
						@if($trade['parameters']['result']['net_sum'] > 0)
							class="text-success"
						@else
							class="text-danger"
						@endif>
						{{number_format($trade['parameters']['result']['net_sum'] * 8.72, 0, '.', ' ')}} kr<small style="float: right;">{{number_format($trade['parameters']['result']['net_percentage'], 2, '.', '')}}%</small></span></td>
						<td><small>{{number_format($trade['parameters']['result']['fees']['total'], 2, '.', '')}} ({{number_format($trade['parameters']['result']['fees']['total_avg_percentage'], 2, '.', '')}}%)</small></td>
					<td>
						<small @if ($trade['parameters']['duration']['hours'] == 0) class="text-muted" @endif>
							{{$trade['parameters']['duration']['hours']}}h
						</small>
						<small @if ($trade['parameters']['duration']['hours'] == 0 && $trade['parameters']['duration']['minutes'] == 0) class="text-muted" @endif>
							{{$trade['parameters']['duration']['minutes']}}m
						</small>
						<small>
							{{$trade['parameters']['duration']['seconds']}}s
						</small>
					</small></td>
					<td><small>{{$trade['parameters']['balance']}}</small></td>
					<td><a @if (! $trade['trades'][0]['comment']) class="text-danger" @else class="text-white" @endif href="/trades/edit/{{$trade['parameters']['bitfinex_id']}}"><i class="far fa-edit"></i></a></td>
				</tr>
			@endforeach
		</tbody>
	</table>
@endsection