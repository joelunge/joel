<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Crypto - Dashboard') }}</title>
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
					  <tbody>
					  	@foreach ($tickers as $ticker)
					  	<tr>
					  		<td class="text-center" style="width: 25%;">{{str_replace('t', '', str_replace('USD', '', $ticker->ticker))}}</td>
					  		<td class="text-center" style="width: 25%;">{{round($ticker->lastPrice, 6)}}</td>
					  		<td class="text-center" style="width: 25%;">@if ($ticker->dailyChange > 0) <span class="text-success">{{$ticker->dailyChange * 100}} @else <span class="text-danger">{{$ticker->dailyChange * 100}}@endif</span>%</td>
					  		<td class="text-center" style="width: 25%;">{{$ticker->formattedVolume}}</td>
					  		<td class="text-center" style="width: 25%;"><a class="text-center btn btn-xs btn-block btn-dark" href="{{sprintf('https://www.tradingview.com/chart?symbol=BITFINEX%s%s', '%3A', strtoupper(str_replace('t', '', $ticker->ticker)))}}" role="button"><i class="fas fa-chart-area"></i></a></td>
					  		<td class="text-center" style="width: 25%;"><a class="text-center btn btn-xs btn-block btn-danger" href="/trade" role="button"><i class="fas fa-money-bill-alt"></i></td>
					  	</tr>
					  	@endforeach
					  </tbody>
					</table>
				</div>
			</div>
    	</main>
	</div>
</body>