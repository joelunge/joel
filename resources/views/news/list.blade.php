@extends('layouts.default')

@section('title', 'News')

@section('content')
	<ul class="nav nav-pills">
      <li style="margin-right: 15px;">
      	<a @if($_GET['show'] == "10_trades") class="text-muted" @endif href="?show=10_trades">10 trades </a>
      </li>
      <li style="margin-right: 15px;">
      	<a @if($_GET['show'] == "7_days") class="text-muted" @endif href="?show=7_days">7 days </a>
      </li>
      <li style="margin-right: 15px;">
      	<a @if($_GET['show'] == "30_days") class="text-muted" @endif href="?show=30_days">30 days </a>
      </li>
      <li style="margin-right: 15px;">
      	<a @if($_GET['show'] == "3_months") class="text-muted" @endif href="?show=3_months">3 months </a>
      </li>
      <li style="margin-right: 15px;">
      	<a @if($_GET['show'] == "all") class="text-muted" @endif href="?show=all">All </a>
      </li>
    </ul>
	<hr />
	<table class="table table-dark">
		<thead>
			<tr>
				<td>Date</td>
			</tr>
		</thead>
		<tbody>
			@foreach ($news as $key => $new)

			@endforeach
		</tbody>
	</table>
@endsection