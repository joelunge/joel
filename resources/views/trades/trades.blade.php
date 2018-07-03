@extends('layouts.default')

@section('title', 'Trades')

@section('content')
	<table>
		<thead>
			<tr>
				<td>BFX ID</td>
				<td>Coin</td>
				<td>Amount</td>
				<td>Price</td>
				<td>Fee</td>
				<td>Date</td>
			</tr>
		</thead>
		<tbody>
			@foreach ($trades as $key => $trade)
				<tr>
					<td>{{$trade['bitfinex_id']}}</td>
					<td>{{$trade['coin']}}</td>
					<td>{{$trade['amount']}}</td>
					<td>{{$trade['price']}}</td>
					<td>{{$trade['fee']}}</td>
					<td>{{$trade['date']}}</td>
				</tr>
			@endforeach
		</tbody>
	</table>
@endsection