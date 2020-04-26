<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Crypto - Toggle') }}</title>
    <link rel="icon" href="{{ asset('favicon.ico')}}" />
    <!-- Scripts -->
    <script src="{{ asset('js/app.js') }}" defer></script>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Raleway:300,400,600" rel="stylesheet" type="text/css">

    <script defer src="https://use.fontawesome.com/releases/v5.0.8/js/all.js"></script>

    <script src="https://code.highcharts.com/highcharts.js"></script>
    <script src="https://code.highcharts.com/modules/series-label.js"></script>
    <script src="https://code.highcharts.com/modules/exporting.js"></script>
    <script src="https://code.highcharts.com/modules/export-data.js"></script>

    <script src="https://code.jquery.com/jquery-3.3.1.min.js" integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8=" crossorigin="anonymous"></script>

    <!-- Styles -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
</head>
<body style="background-color: #1b262d;">
    <div class="container" id="app" style="max-width: 800px;">
    	@include('includes.header')
    	<main class="py-4">
    		<div class="row">
		  		<div class="col-md-12">
				    <table class="table table-sm table-dark">
                      <thead>
                          <th></th>
                          <th class="text-center">Coin</th>
                          <th class="text-center">Direction</th>
                          <th class="text-center">Base</th>
                          <th class="text-center">Trade Size</th>
                          <th class="text-center">PL</th>
                          <th class="text-center">PL%</th>
                      </thead>
					  <tbody>
					  	@foreach ($positions as $position)
					  	<tr>
                            @if ($position['amount'] > 0)
                            <td class="text-center"><i class="fas fa-chevron-circle-up text-success"></i></td>
                            @else
                            <td class="text-center"><i class="fas fa-chevron-circle-down text-danger"></i></td>
                            @endif
					  		<td class="text-center">{{strtoupper(str_replace('usd', '', $position['symbol']))}}</td>
                            <td class="text-center">@if ($position['amount'] > 0) LONG @else SHORT @endif</td>
                            <td class="text-center">{{$position['base']}}</td>
                            <td class="text-center">{{abs(round($position['amount'] * $position['base']))}} USD</td>
                            <td class="text-center @if ($position['pl'] > 0) text-success @else text-danger @endif">{{round($position['pl'], 2)}} USD</td>
                            <td class="text-center @if ($position['pl'] > 0) text-success @else text-danger @endif">{{round(($position['pl'] / (abs($position['amount']) * $position['base'])) * 100, 2)}}%</td>
					  	</tr>

                        <tr>
                            <td></td>
                            @foreach ($orders as $order)
                                @if ($order['type'] == 'stop')
                                    <td class="text-center" colspan="3">Stop: {{round((($position['base'] - $order['price']) / $position['base']) * 100, 2)}}% ({{$order['price']}})</td>
                                @endif
                            @endforeach

                            @foreach ($orders as $order)
                                @if ($order['type'] == 'limit')
                                    <td class="text-center" colspan="3">Target: {{abs(round((($order['price'] - $position['base']) / $position['base']) * 100, 2))}}% ({{$order['price']}})</td>
                                @endif
                            @endforeach
                        </tr>

                        <tr>
                            @foreach ($orders as $order)
                                @if ($order['type'] == 'stop')
                                    <td class="text-center" colspan="4"><a class="btn btn-block btn-dark text-center" href="/orders/edit/{{strtoupper($position['symbol'])}}/{{($position['amount'] * -1)}}/{{$order['id']}}/stop" role="button">Edit stop</a></td>
                                @endif
                            @endforeach

                            @foreach ($orders as $order)
                                @if ($order['type'] == 'limit')
                                    <td class="text-center" colspan="3"><a class="btn btn-block btn-dark text-center" href="/orders/edit/{{strtoupper($position['symbol'])}}/{{($position['amount'] * -1)}}/{{$order['id']}}/limit" role="button">Edit target</a></td>
                                @endif

                                @if (count($orders) <= 1)
                                    <td class="text-center" colspan="3"><a class="btn btn-block btn-success text-center" href="/orders/new/{{strtoupper($position['symbol'])}}/{{($position['amount'] * -1)}}" role="button">Add target</a></td>
                                @endif
                            @endforeach
                        </tr>

                        <tr>
                            @if ($position['amount'] > 0)
                            <td class="text-center" colspan="7"><a class="btn btn-block @if ($position['pl'] > 0) btn-success @else btn-danger @endif text-center" href="/trade/new/{{strtoupper($position['symbol'])}}/{{$position['amount']*-1}}/{{($position['base'] * 1.2)}}/sell/market" role="button" onclick="return confirm('Are you sure?')">@if ($position['pl'] > 0) TAKE PROFIT @else TAKE LOSS @endif</a></td>
                            @else
                            <td class="text-center" colspan="7"><a class="btn btn-block @if ($position['pl'] > 0) btn-success @else btn-danger @endif text-center" href="/trade/new/{{strtoupper($position['symbol'])}}/{{$position['amount']*-1}}/{{($position['base'] * 0.8)}}/buy/market" role="button" onclick="return confirm('Are you sure?')">@if ($position['pl'] > 0) TAKE PROFIT @else TAKE LOSS @endif</a></td>
                            @endif
                        </tr>
					  	@endforeach
					  </tbody>
					</table>
				</div>
			</div>
    	</main>
	</div>
</body>