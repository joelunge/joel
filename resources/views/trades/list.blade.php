@extends('layouts.default')

@section('title', 'Trades')

@section('content')
	<table class="table table-dark">
		<thead>
			<tr>
				<td>Date</td>
				<td>Result</td>
				<td>Balance</td>
				<td>Fees</td>
				<td>Type</td>
				<td>Coin</td>
			</tr>
		</thead>
		<tbody>
			@foreach ($trades as $key => $trade)
				<tr>
					<td>{{$trade['parameters']['date']}}</td>
					<td><span
						@if($trade['parameters']['result']['net_sum'] > 0)
							class="text-success"
						@else
							class="text-danger"
						@endif>
						{{$trade['parameters']['result']['net_sum']}}</span></td>
					<td>{{$trade['parameters']['balance']}}</td>
					<td>{{round($trade['parameters']['result']['fees']['total'], 2)}}</td>
					<td>
						@if($trade['parameters']['type'] == 'Short')<i style="transform: rotate(180deg) scaleX(-1);" class="text-danger fas fa-chart-line"></i> {{$trade['parameters']['type']}}@endif
						@if($trade['parameters']['type'] == 'Long')<i class="text-success fas fa-chart-line"></i> {{$trade['parameters']['type']}}@endif
					</td>
					<td>{{$trade['parameters']['coin']}}</td>
				</tr>
			@endforeach
		</tbody>
	</table>
@endsection