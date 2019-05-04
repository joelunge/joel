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

    <link rel="icon" href="{{ asset(favicon.ico}}" />

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
<body>
	@include('includes.header')
    <div class="container" id="app">
    	<main class="py-4">