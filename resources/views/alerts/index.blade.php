<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Alerts - Index') }}</title>
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
		  			<a class="text-center btn btn-xs btn-block btn-success" href="/alerts/add" role="button">New Alert</a>
		  			<br />
		  		</div>
		  		<div class="col-md-12">
				    <table class="table table-dark">
					  <tbody>
					  	@if ($alerts->count())
						  	@foreach ($alerts as $alert)
						    <tr>
						      <td style="text-align: center; width: 20%;">{{strtoupper($alert->ticker)}}</td>
						      <td style="text-align: center; width: 20%;">{{$alert->price}}</td>
						      <td style="text-align: center; width: 20%;">@if ($alert->direction == 'down') <i class="text-danger fas fa-chevron-down"></i> @else <i class="text-success fas fa-chevron-up"></i>@endif</td>
                              <td style="text-align: center; width: 20%;">
                                @if ($alert->enabledisable == 'enable')
                                <i class="fas text-success fa-check-circle"></i>
                                @elseif ($alert->enabledisable == 'disable')
                                <i class="fas text-danger fa-times-circle"></i>@endif

                                @if ($alert->enabledisable_direction == 'buy')
                                <i class="fas text-success fa-arrow-alt-circle-up"></i>
                                @elseif ($alert->enabledisable_direction == 'sell')
                                <i class="fas text-danger fa-arrow-alt-circle-down"></i>
                                @endif
                              </td>
						      <td style="text-align: center; width: 20%;"><a style="margin-right: 20px;" class="btn btn-xs btn-dark" href="/alerts/edit/{{$alert->id}}" role="button">Edit</a></td>
						    </tr>
						    @endforeach
					   	@else
					   		<h2 style="text-align: center; color: white;">No alerts</h2>
					   	@endif
					  </tbody>
					</table>
				</div>
			</div>
    	</main>
	</div>
</body>