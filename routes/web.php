<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
// 	$baseBalance = 7242;
// 	$regulator = 10;

// 	$changes = [
// 		'1.01',
// 		'1.01',
// 		'1.00',
// 		'1.00',
// 		'1.00',
// 		'1.02',
// 		'1.02',
// 		'0.99',
// 		'-0.6',
// 		// '-0.21',
// 		// '0.08',
// 		// '1.48',
// 		// '0.20',
// 		// '-0.12',
// 		// '1.01',
// 		// '0.17',
// 		// '0.78',
// 		// '0.75',
// 		// '0.10',
// 		// '-1.09',
// 		// '-1.83',
// 		// '0.76',
// 		// '0.36',
// 		// '1.66',
// 		// '3.14',
// 		// '1.30',
// 		// '1.77',
// 		// '-2.46',
// 		// '0.99',
// 		// '-2.6',
// 		// '0.59',
// 		// '1.29',
// 		// '3.12',
// 		// '-6.08',
// 	];

// 	foreach ($changes as $key => $change) {
// 		echo $baseBalance * $change;
// 		echo "<br>";
// 		echo $baseBalance . "*" . "((100+".$change.")/100)";
// 		echo "<br>";
// 		$baseBalance = $baseBalance * ((100+$change)/100);
// 	}
});

Route::get('trades', 'TradesController@list');