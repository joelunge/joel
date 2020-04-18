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
Route::get('/old-index', 'TradesController@dashboard')->name('dashboard-old')->middleware('auth');
Route::get('/', 'DashboardController@index')->name('dashboard');
Route::get('/trades/import', 'TradesController@import')->name('import');
Route::get('/hot', 'TradesController@hot')->name('hot');
Route::get('/hot/{coin}', 'TradesController@hotSingle')->name('hot_single');
Route::post('/trades/import/upload', 'TradesController@upload')->name('upload');
Route::get('/trades/scrape', 'TradesController@scrape')->name('scrape_trades');
Route::get('/trades/scrape_candles', 'TradesController@scrapeCandles')->name('scrape_candles');
Route::get('/trades/bfxtrades', 'TradesController@showBfxTrades')->name('show_bfx_trades');
Route::get('/trades/bfxcandles', 'TradesController@showBfxCandles')->name('show_bfx_candles');
Route::get('/make_indicators', 'TradesController@makeIndicators')->name('make_indicators');
Route::get('/update_rsi', 'TradesController@updateRsi')->name('update_rsi');
Route::get('/normalize_volume', 'TradesController@normalizeVolume')->name('normalize_volume');
Route::get('/analyze_uptrends', 'TradesController@analyzeUptrends')->name('analyze_uptrends');
Route::get('news', 'NewsController@list')->name('news');
Route::get('fill_empty_minutes', 'TradesController@fillEmptyMinutes')->name('fill_empty_minutes');

Route::get('/alerts/alert', 'AlertsController@alert')->name('alerts_alert');
Route::get('/alerts', 'AlertsController@index')->name('alerts');
Route::get('/alerts/edit', 'AlertsController@edit')->name('alerts_edit');
Route::post('/alerts/store/{id}', 'AlertsController@store')->name('alerts_store');
Route::get('/alerts/delete/{id}', 'AlertsController@delete')->name('alerts_delete');
Route::get('/alerts/edit/{id}', 'AlertsController@edit')->name('alerts_edit');
Route::get('/alerts/add', 'AlertsController@add')->name('alerts_add');

Route::get('/coins', 'CoinsController@index')->name('coins');

Route::get('/toggle', 'DashboardController@toggle')->name('toggle');

Route::get('/trades/{enabledisable}/{coin}/{direction}', 'TradesController@enabledisable')->name('enabledisable');

Route::get('/candles/scrape_hist', 'CandlesController@scrapeHist')->name('candles_scrape_hist');

Route::get('/test', 'TradesController@test')->name('test');

Route::get('/backtest', 'BacktestController@index')->name('backtest');