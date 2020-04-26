<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Orders - New') }}</title>

    <!-- Scripts -->
    <script src="{{ asset('js/app.js') }}" defer></script>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Raleway:300,400,600" rel="stylesheet" type="text/css">

    <script defer src="https://use.fontawesome.com/releases/v5.0.8/js/all.js"></script>
    <link rel="icon" href="{{ asset('favicon.ico')}}" />
    <script src="https://code.highcharts.com/highcharts.js"></script>
    <script src="https://code.highcharts.com/modules/series-label.js"></script>
    <script src="https://code.highcharts.com/modules/exporting.js"></script>
    <script src="https://code.highcharts.com/modules/export-data.js"></script>

    <script src="https://code.jquery.com/jquery-3.3.1.min.js" integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8=" crossorigin="anonymous"></script>

    <!-- Styles -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
</head>
<body style="font-size: .6rem; background-color: #1b262d;">
    <div class="container" id="app" style="max-width: 800px;">
    	<main class="py-4">
		    <form method="post" action="/orders/sendorder" enctype="multipart/form-data">
			{{ csrf_field() }}
			<div class="form-group">
				<label for="ticker">Ticker</label>
				<select class="form-control" name="ticker" id="ticker">
				<option value="{{$coin}}">{{str_replace('t', '', str_replace('USD', '', $coin))}}</option>
				</select>
			</div>
			<div class="form-group">
				<label for="amount">Amount</label>
				<input type="text" class="form-control" id="amount" name="amount" aria-describedby="amount" placeholder="Amount" value="{{$amount}}">
			</div>

			<div class="form-group">
				<label for="ticker">Target</label>
				<select class="form-control" name="target" id="target">
				<option value="0.5">0.5%</option>
				<option value="1">1%</option>
				<option value="1.5">1.5%</option>
				<option selected value="2">2%</option>
				<option value="2.5">2.5%</option>
				<option value="3">3%</option>
				<option value="3.5">3.5%</option>
				<option value="4">4%</option>
				<option value="4.5">4.5%</option>
				<option value="5">5%</option>
				<option value="6">6%</option>
				<option value="7">7%</option>
				<option value="8">8%</option>
				<option value="9">9%</option>
				<option value="10">10%</option>
				<option value="11">11%</option>
				<option value="12">12%</option>
				<option value="13">13%</option>
				<option value="14">14%</option>
				<option value="15">15%</option>
				</select>
			</div>
			
			<hr />
			<button type="submit" class="btn btn-block btn-success">Submit</button>
			</form>
    	</main>
</div>
</body>