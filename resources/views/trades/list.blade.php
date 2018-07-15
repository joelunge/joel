@extends('layouts.default')

@section('title', 'Trades')

@section('content')
	<div class="row">
		<div class="col-sm-6">
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
		</div>
	    <div class="col-sm-6">
		    <div class="dropdown float-right">
			  <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
			    Users
			  </button>
			  <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
			    <a class="dropdown-item" href="#">Joel</a>
			    <a class="dropdown-item" href="#">Markus</a>
			    <a class="dropdown-item" href="#">Both</a>
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
			<span class="text-uppercase">Gain (Stämmer inte - fixa funding cost)</span>
		</div>
		<div class="col-sm-3 text-center">
			<h1 style="font-size: 48px; margin-bottom: 0;" @if ($stats['net_sum'] > 0) class="text-success" @else class="text-danger" @endif>{{number_format($stats['net_sum'] * 1, 0, '.', ' ')}} kr</h1>
			<span class="text-uppercase">Gain (stämmer inte - fixa funding cost)</span>
		</div>		
	</div>
	<hr />
	<table class="table table-dark">
		<thead>
			<tr>
				<th>Date</th>
				<th>Coin</th>
				<th>Result SEK</th>
				<th>Result USD</th>
				<th>Result %</th>
				<th>Fees</th>
				<th>Duration</th>
				<th>Balance</th>
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
					<td><small>{{round($trade['parameters']['balance'])}}</small></td>
					<td><a @if (! $trade['trades'][0]['comment']) class="text-danger" @else class="text-white" @endif href="/trades/edit/{{$trade['parameters']['bitfinex_id']}}"><i class="far fa-edit"></i></a></td>
				</tr>
			@endforeach
		</tbody>
	</table>
@endsection