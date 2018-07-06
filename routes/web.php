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

Auth::routes();
Route::get('trades', 'TradesController@list')->name('trades')->middleware('auth');
Route::get('trades/edit/{bitfinex_id}', 'TradesController@edit')->middleware('auth');
Route::post('trades/update/{bitfinex_id}','TradesController@update')->middleware('auth');
Route::get('/', 'DashboardController@index')->name('dashboard')->middleware('auth');
Route::get('/trades/import', 'TradesController@import')->name('import');
Route::post('/trades/import/upload', 'TradesController@upload')->name('upload');
Route::get('news', 'NewsController@list')->name('news');