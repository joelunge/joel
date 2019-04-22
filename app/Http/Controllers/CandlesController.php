<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App;
use H;
use Csv;
use DB;
use Request;
use Auth;

class CandlesController extends Controller
{
    /**
     * Show the profile for the given user.
     *
     * @param  int  $id
     * @return Response
     */

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function scrapeHist()
    {
    	$coin = 'XRP';
        $timeframe = '15m';
        $loop = 30;

        for ($i=0; $i < $loop; $i++) {
            sleep(4);
            // $start = 1495216644000;
            // $end = $start + 86400000; // 24 hours

            $lastCandle = App\Btccandle::orderBy('id', 'desc')->first();

            $start = $lastCandle->timestamp;
            $end = $start + (86400000 * 1);

            $request = sprintf('https://api.bitfinex.com/v2/candles/trade:%s:t%sUSD/hist?sort=1&limit=5000&start=%s', $timeframe, $coin, $start, $end);

            $candles = file_get_contents($request);
            $candles = json_decode($candles);

            foreach ($candles as $candle) {
                $year = date('Y', $candle[0] / 1000);
                $month = date('m', $candle[0] / 1000);
                $day = date('d', $candle[0] / 1000);

                if ($year == 2019 && $month == 02 && $day == 02) {
                    echo "wrong start and end timestamps"; exit;
                }

                DB::statement(sprintf('insert ignore into %s_%s_candles (timestamp, open, close, high, low, volume, updated_at, created_at) values (%s, %s, %s, %s, %s, %s, "%s", "%s")', $coin, $timeframe, $candle[0], $candle[1], $candle[2], $candle[3], $candle[4], $candle[5], date('Y-m-d H:i:s', time()), date('Y-m-d H:i:s', time())));
                // $exist = App\Btccandle::where('timestamp', $candle[0])->first();
                // if ($exist == null) {
                //     $bfxcandle = new App\Btccandle;
                //     $bfxcandle->timestamp = $candle[0];
                //     $bfxcandle->open = $candle[1];
                //     $bfxcandle->close = $candle[2];
                //     $bfxcandle->high = $candle[3];
                //     $bfxcandle->low = $candle[4];
                //     $bfxcandle->volume = $candle[5];
                //     $bfxcandle->save();
                // }
            }
        }
    }
}