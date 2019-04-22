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
			@foreach ($candles as $candle)
				@if ($candle->close > $candle->open) @php ($opacity = abs(1-($candle->open / $candle->close)) * 100) @else @php ($opacity = abs(1-($candle->close / $candle->open)) * 100) @endif
				@if ($candle->close > $candle->open)
				<tr style="color: white; background: rgba(157, 194, 74, {{$opacity}}); border-left: 2px solid rgba(157, 194, 74, 1); border-bottom: 1px solid rgba(157, 194, 74, .3)">
				@else
				<tr style="color: white; background: rgba(225, 86, 86, {{$opacity}}); border-bottom: 1px solid rgba(225, 86, 86, .3); border-left: 2px solid rgba(225, 86, 86, 1);">
				@endif
				<td style="border: none;">{{$candle->date}}</td>
				@if ($candle->close > $candle->open)
					<td style="border: none;">{{number_format((1-($candle->open / $candle->close)) * 100, 2)}}</td>
				@else
					<td style="border: none;">{{number_format((1-($candle->close / $candle->open)) * 100, 2)}}</td>
				@endif
				<td style="border: none;">{{$candle->close}}</td>
				<td style="border: none;">{{$candle->tradeCount}}</td>
                <td style="border: none;">{{$candle->changedPrice}}</td>
                <td style="text-align: right; border: none;">@if ($candle->buyVolumeUsd && $candle->sellVolumeUsd) x{{round($candle->buyVolumeUsd / $candle->sellVolumeUsd)}} @endif</td>
                <td style="text-align: right; border: none;">{{number_format($candle->buyVolumeUsd, 0, ' ', ' ')}}</td>
				<td style="text-align: right; border: none;">{{number_format($candle->sellVolumeUsd, 0, ' ', ' ')}}</td>
			</tr>
			@endforeach
		</tbody>
	</table>
</div>
</body>