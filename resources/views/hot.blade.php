@extends('layouts.default')

@section('title', 'Hot')

@section('content')
	<table class="table table-dark table-trades">
		<thead>
			<tr>
				<th>Coin</th>
				<th>Avg. Count</th>
				<th>Avg. ChangedPrice</th>
				<th>Count 1m</th>
				<th>Changed price 1m</th>
				<th>Changed price 2m</th>
				<th>Changed price 3m</th>
			</tr>
		</thead>
		<tbody>
			@foreach ($coins as $key => $coin)
				<tr>
					<td>{{strtoupper(str_replace('usds', '', str_replace('trades-t', '', $key)))}}</td>
					<td>{{round($coin['avgCount'])}}</td>
					<td>{{round($coin['avgChangedPrice'])}}</td>
					<td>{{round($coin['lastMinute']['count'])}} @if ($coin['avgCount']) ({{round($coin['lastMinute']['count'] / $coin['avgCount'])}}) @endif</td>
					<td>{{round($coin['lastMinute']['changedPrice'])}}</td>
				</tr>
			@endforeach
		</tbody>
	</table>
@endsection