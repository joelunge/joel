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

// Auth::routes();
Route::get('trades', 'TradesController@list')->name('trades')->middleware('auth');
Route::get('trades/edit/{bitfinex_id}', 'TradesController@edit')->middleware('auth');
Route::post('trades/update/{bitfinex_id}','TradesController@update')->middleware('auth');
Route::get('/', 'TradesController@dashboard')->name('dashboard')->middleware('auth');
Route::get('/trades/import', 'TradesController@import')->name('import');
Route::get('/hot', 'TradesController@hot')->name('hot');
Route::get('/hot/{coin}', 'TradesController@hotSingle')->name('hot_single');
Route::post('/trades/import/upload', 'TradesController@upload')->name('upload');
Route::get('/trades/scrape', 'TradesController@scrape')->name('scrape_trades');
Route::get('/trades/scrape_candles', 'TradesController@scrapeCandles')->name('scrape_candles');
Route::get('/trades/bfxtrades', 'TradesController@showBfxTrades')->name('show_bfx_trades');
Route::get('/make_indicators', 'TradesController@makeIndicators')->name('make_indicators');
Route::get('news', 'NewsController@list')->name('news');