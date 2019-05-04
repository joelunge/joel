<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Alerts - Edit') }}</title>

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
    <div class="container" id="app" style="max-width: 800px;">
    	<main class="py-4">
		    <form method="post" action="/alerts/store/0" enctype="multipart/form-data">
			{{ csrf_field() }}
			<div class="form-group">
				<label for="ticker">Ticker</label>
				<select class="form-control" name="ticker" id="ticker">
				@foreach ($tickers as $ticker)
				<option value="{{strtolower(str_replace('t', '', str_replace('USD', '', $ticker->ticker)))}}">{{str_replace('t', '', str_replace('USD', '', $ticker->ticker))}}</option>
				@endforeach
				</select>
			</div>
			<div class="form-group">
				<label for="price">Price</label>
				<input type="text" class="form-control" id="price" name="price" aria-describedby="price" placeholder="Price">
			</div>
			<div class="form-group">
				<label for="comment">Comment</label>
				<input type="text" class="form-control" name="comment" id="comment" placeholder="Comment">
			</div>
			<br />
			<div style="width: 100%;" class="btn-group btn-group-toggle" data-toggle="buttons">
			  <label style="width: 50%;" class="btn btn-secondary active">
			    <input type="radio" value="up" name="direction" id="up" autocomplete="off" checked> UP
			  </label>
			  <label style="width: 50%;" class="btn btn-secondary">
			    <input type="radio" value="down" name="direction" id="down" autocomplete="off"> DOWN
			  </label>
			</div>
			<hr />
			<button type="submit" class="btn btn-block btn-success">Submit</button>
			</form>
    	</main>
</div>
</body>