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

    <script>
        $(function() {
            $('#show_indicators').click(function() {
                $('.table-trades').toggleClass('d-none');
                $('.table-indicators').toggleClass('d-none');
            });

            $('#add-reason-fail').click(function() {
                $("#reason-fail:last-of-type").append('<li class="list-group-item"><input type="text" name="new_reason_fail[]" /></li>');
                var addReasonFailLi = $('#add-reason-fail-li');
                $("#reason-fail:last-of-type").append(addReasonFailLi);
            });

            $('#add-reason-success').click(function() {
                $("#reason-success:last-of-type").append('<li class="list-group-item"><input type="text" name="new_reason_success[]" /></li>');
                var addReasonSuccessLi = $('#add-reason-success-li');
                $("#reason-success:last-of-type").append(addReasonSuccessLi);
            });

            $('#show-all-fail').click(function() {
                $('#reason-fail li').removeClass('d-none');
                $('#reason-fail li').addClass('list-group-item');
            });

            $('#show-all-success').click(function() {
                $('#reason-success li').removeClass('d-none');
                $('#reason-success li').addClass('list-group-item');
            });
        });
    </script>
</head>
<body style="font-size: .6rem; background-color: #1b262d;">
    <div class="container" id="app" style="width: 800px;">
    	<main class="py-4">
    	</main>

	<table class="table table-sm">
		<tbody>
			@php ($previousMin = null)
			@foreach ($trades as $trade)
				@php ($opacity = abs(($trade->amount * $trade->price) / 50000))
				@if (date('i', $trade->timestamp / 1000) != $previousMin) <tr style="border: none !important; border-left: 2px solid #000 !important; background-color: rgb(255, 255, 255, .2); opacity: 1;"><td style="border: none !important;"><td style="border: none !important;"><td style="border: none !important;"><span style="opacity: 0;">z</td></tr> @endif
				@if ($trade->amount > 0) <tr style="color: white; background: rgba(157, 194, 74, {{$opacity}}); border-left: 2px solid rgba(157, 194, 74, 1); border-bottom: 1px solid rgba(157, 194, 74, .3)"> @else <tr style="color: white; background: rgba(225, 86, 86, {{$opacity}}); border-bottom: 1px solid rgba(225, 86, 86, .3); border-left: 2px solid rgba(225, 86, 86, 1);"> @endif
				<td style="border: none;">{{date('Y-m-d H:i:s', $trade->timestamp / 1000)}} - {{str_replace('15460', '', $trade->timestamp)}}</td>
				<td style="text-align: right; border: none;">{{number_format(abs(round($trade->amount * $trade->price)), 0, ' ', ' ')}}</td>
				<td style="border: none;">{{$trade->price}}</td>
				@php ($previousMin = date('i', $trade->timestamp / 1000))
			</tr>
			@endforeach
		</tbody>
	</table>
</div>
</body>