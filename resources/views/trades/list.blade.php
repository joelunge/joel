@extends('layouts.default')

@section('title', 'Trades')

@section('content')
	@if (! $isAllowedToTrade)
		<div class="row">
			<div class="col-md-12">
				<div class="alert alert-danger" role="alert">
				  <h4 class="alert-heading">NOT ALLOWED TO TRADE!</h4>
				  <p>You are <strong>not allowed to trade</strong> due to unresolved losses.</p>
			  		<p>To keep trading, you need to resolve your previous losses to make sure you never do the same mistakes again.</p>
				</div>
			</div>
		</div>
	@endif
	<div class="row">
		<div class="col-sm-6">
			<ul class="nav nav-pills">
		      <li style="margin-right: 15px;">
		      	<a @if($_GET['show'] == "10_trades") class="text-muted" @endif href="@if (isset($_GET['user'])) ?show=10_trades&user={{$_GET['user']}} @else ?show=10_trades @endif">10 trades </a>
		      </li>
		      <li style="margin-right: 15px;">
		      	<a @if($_GET['show'] == "7_days") class="text-muted" @endif href="@if (isset($_GET['user'])) ?show=7_days&user={{$_GET['user']}} @else ?show=7_days @endif">7 days </a>
		      </li>
		      <li style="margin-right: 15px;">
		      	<a @if($_GET['show'] == "30_days") class="text-muted" @endif href="@if (isset($_GET['user'])) ?show=30_days&user={{$_GET['user']}} @else ?show=30_days @endif">30 days </a>
		      </li>
		      <li style="margin-right: 15px;">
		      	<a @if($_GET['show'] == "3_months") class="text-muted" @endif href="@if (isset($_GET['user'])) ?show=3_months&user={{$_GET['user']}} @else ?show=3_months @endif">3 months </a>
		      </li>
		      <li style="margin-right: 15px;">
		      	<a @if($_GET['show'] == "all") class="text-muted" @endif href="@if (isset($_GET['user'])) ?show=all&user={{$_GET['user']}} @else ?show=all @endif">All </a>
		      </li>
		    </ul>
		</div>
	    <div class="col-sm-6">
		    <div class="dropdown float-right">
		    	<button class="btn btn-secondary" type="button" id="show_indicators" class="show_indicators">Indicators</button>
			  <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
			    Users
			  </button>
			  <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
			    <a class="dropdown-item" href="{{str_replace(['&user=1', '&user=2', '&user=all'], '', $_SERVER['REQUEST_URI'])}}&user=1">Joel</a>
			    <a class="dropdown-item" href="{{str_replace(['&user=1', '&user=2', '&user=all'], '', $_SERVER['REQUEST_URI'])}}&user=2">Markus</a>
			    <a class="dropdown-item" href="{{str_replace(['&user=1', '&user=2', '&user=all'], '', $_SERVER['REQUEST_URI'])}}&user=all">All</a>
			  </div>
			</div>
    	</div>
	</div>
	<hr />
	<div class="row">
		<div class="col-sm-3 text-center">
			<h1 style="font-size: 48px; margin-bottom: 0;" @if ($stats['winrate'] > 50) class="text-success" @else class="text-danger" @endif>{{round($stats['winrate'], 1)}}%</h1>
			<span class="text-uppercase">Winrate</span>
		</div>
		<div class="col-sm-3 text-center">
			<h1 style="font-size: 48px; margin-bottom: 0;">{{$stats['wins']}} - {{$stats['losses']}}</h1>
			<span class="text-uppercase">Winrate</span>
		</div>
		<div class="col-sm-3 text-center">
			<h1 style="font-size: 48px; margin-bottom: 0;" @if ($stats['net_percentage'] > 0) class="text-success" @else class="text-danger" @endif>{{round($stats['net_percentage'], 2)}}%</h1>
			<span class="text-uppercase">Gain</span>
		</div>
		<div class="col-sm-3 text-center">
			<h1 style="font-size: 48px; margin-bottom: 0;" @if ($stats['net_sum'] > 0) class="text-success" @else class="text-danger" @endif>{{number_format($stats['net_sum'] * \App\Currency::find(1)->value, 0, '.', ' ')}} kr</h1>
			<span class="text-uppercase">Gain</span>
		</div>
		<div class="col-sm-3 text-center">
			<h1 style="font-size: 48px; margin-bottom: 0;" @if ($stats['net_sum'] > 0) class="text-success" @else class="text-danger" @endif>{{number_format($stats['net_sum'] * 1, 0, '.', ' ')}} usd</h1>
			<span class="text-uppercase">Gain</span>
		</div>
	</div>
	<hr />

	<table class="table table-dark table-trades">
		<thead>
			<tr>
				<th>Date</th>
				<th>Coin</th>
				<th>Result SEK</th>
				<th>Result USD</th>
				<th>Result %</th>
				<th>Fees</th>
				<th>Duration</th>
				@if (isset($_GET['user']) && $_GET['user'] == 'all')
					<th>User</th>
				@else
					<th>Balance</th>
				@endif
				<th></th>
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
					<td>
						<span
						@if($trade['parameters']['result']['net_sum'] > 0)
							class="text-success"
						@else
							class="text-danger"
						@endif>
						{{number_format($trade['parameters']['result']['net_sum'] * \App\Currency::find(1)->value, 0, '.', ' ')}} kr @if (! $trade['trades'][0]['resolved'] && $trade['parameters']['result']['net_sum'] < 0) <i class="fas fa-exclamation-triangle"></i> @endif
						</span>
					</td>
					<td><span
						@if($trade['parameters']['result']['net_sum'] > 0)
							class="text-success"
						@else
							class="text-danger"
						@endif>
						<small>{{round($trade['parameters']['result']['net_sum'])}} USD</small>
					</td>
					<td><span
						@if($trade['parameters']['result']['net_sum'] > 0)
							class="text-success"
						@else
							class="text-danger"
						@endif>
						<small>{{number_format($trade['parameters']['result']['net_percentage'], 2, '.', '')}}%</small></span>
					</td>
					<td>
						<small>{{number_format($trade['parameters']['result']['fees']['total'], 2, '.', '')}} ({{number_format($trade['parameters']['result']['fees']['total_avg_percentage'], 2, '.', '')}}%)</small>
					</td>
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
					</td>
					@if (isset($_GET['user']) && $_GET['user'] == 'all')
						<td><small>@if ($trade['trades'][0]['user_id'] == 1) Joel @else Markus @endif</small></td>
					@else
						<td><small>{{round($trade['parameters']['balance'])}}</small></td>
					@endif
					<td><a @if (! $trade['trades'][0]['comment']) class="text-danger" @else class="text-white" @endif href="/trades/edit/{{$trade['parameters']['bitfinex_id']}}"><i class="far fa-edit"></i></a></td>
				</tr>
			@endforeach
		</tbody>
	</table>

	<table class="table table-dark d-none table-indicators">
		<thead>
			<tr>
				<th>Result %</th>
				@foreach ($indicator_names as $indicatorName)
				<th>{{$indicatorName}}</th>
				@endforeach
				@if (isset($_GET['user']) && $_GET['user'] == 'all')
					<th>User</th>
				@else
					<th>Balance</th>
				@endif
				<th></th>
			</tr>
		</thead>
		<tbody>
			@foreach ($trades as $key => $trade)
				<tr id="{{$trade['parameters']['bitfinex_id']}}">
					<td><span
						@if($trade['parameters']['result']['net_sum'] > 0)
							class="text-success"
						@else
							class="text-danger"
						@endif>
						<small>{{number_format($trade['parameters']['result']['net_percentage'], 2, '.', '')}}%</small></span>
					</td>
					@foreach ($indicator_names as $key => $indicatorName)
						<td>@if (isset($indicators[$key][$trade['trades'][0][$key]])) {{$indicators[$key][$trade['trades'][0][$key]]}} @endif</td>
					@endforeach
					@if (isset($_GET['user']) && $_GET['user'] == 'all')
						<td><small>@if ($trade['trades'][0]['user_id'] == 1) Joel @else Markus @endif</small></td>
					@else
						<td><small>{{round($trade['parameters']['balance'])}}</small></td>
					@endif
					<td><a @if (! $trade['trades'][0]['comment']) class="text-danger" @else class="text-white" @endif href="/trades/edit/{{$trade['parameters']['bitfinex_id']}}"><i class="far fa-edit"></i></a></td>
				</tr>
			@endforeach
		</tbody>
	</table>
@endsection