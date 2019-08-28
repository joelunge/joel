<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

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
<body style="font-size: .6rem; background-color: #1b262d;">
    <div class="container" id="app" style="width: 800px;">
    	<main class="py-4">
            <h1 class="text-white text-center">
                @if ($resultPercentage > 0)+@endif{{round($resultPercentage, 2)}}% - {{round($balance)}}
            </h1>
    	</main>

	<table class="table table-sm">
        <thead style="background-color: white;">
            <th>Date</th>
            <th>Direction</th>
            <th>PL</th>
            <th>Volume</th>
            <th>BuyVolume</th>
            <th>SellVolme</th>
            <th>Price move</th>
            <th>R1</th>
            <th>R5</th>
            <th>R15</th>
            <th>R30</th>
            <th>R60</th>
        </thead>
		<tbody>
			@foreach ($trades as $t)
			@if ($t->resultPercentage > 0)
			<tr style="color: white; background: rgba(157, 194, 74, 1); border-left: 2px solid rgba(157, 194, 74, 1); border-bottom: 1px solid rgba(157, 194, 74, .3)">
			@else
			<tr style="color: white; background: rgba(225, 86, 86, 1); border-bottom: 1px solid rgba(225, 86, 86, .3); border-left: 2px solid rgba(225, 86, 86, 1);">
			@endif
            
				<td>{{$t->date}}</td>
                <td>{{$t->direction}}</td>
                <td>{{round($t->resultPercentage, 2)}}%</td>
                <td>{{number_format($t->volume, 0, ' ', ' ')}}</td>
                <td>{{number_format($t->buyVolume, 0, ' ', ' ')}}</td>
                <td>{{number_format($t->sellVolume, 0, ' ', ' ')}}</td>
                <td>{{round((($t->close-$t->open)/$t->close)*100,2)}}</td>
                <td>{{round($t->rsi_1m)}}</td>
                <td>{{round($t->rsi_5m)}}</td>
                <td>{{round($t->rsi_15m)}}</td>
                <td>{{round($t->rsi_30m)}}</td>
                <td>{{round($t->rsi_1h)}}</td>

			</tr>
			@endforeach
		</tbody>
	</table>
</div>
</body>