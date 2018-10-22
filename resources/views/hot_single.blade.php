@extends('layouts.default')

@section('title', 'Hot Single')

@section('content')
	<table class="table table-dark table-trades">
		<thead>
			<tr>
				<th>Time</th>
				<th>Count</th>
				<th>Changed Price</th>
				<th>Changed Price Up</th>
				<th>Changed Price Down</th>
				<th>Open</th>
				<th>High</th>
				<th>Low</th>
				<th>Close</th>
			</tr>
		</thead>
		<tbody>
			@foreach ($trades as $key => $trade)
				@if ($trade['changedPrice'] > ($avgChangedPrice * 9) || $trade['count'] > ($avgCount * 9))
				<tr>
					<td>{{date('Y-m-d H:i:s', ($trade['timestamp'] / 1000))}}</td>
					<td>{{$trade['count']}}</td>
					<td>{{$trade['changedPrice']}}</td>
					<td>{{$trade['changedPriceUp']}}</td>
					<td>{{$trade['changedPriceDown']}}</td>
					<td>{{$trade['open']}}</td>
					<td>{{$trade['high']}}</td>
					<td>{{$trade['low']}}</td>
					<td>{{$trade['close']}}</td>
				</tr>
				@endif
			@endforeach
		</tbody>
	</table>
@endsection