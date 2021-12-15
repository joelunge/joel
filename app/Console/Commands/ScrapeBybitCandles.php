<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ScrapeBybitCandles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scrape:bybitcandles';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */

    public function handle()
    {
        // Scrape All Coins for Perpetual trading
        $commissions = \App\Commissiontotal::get();

        $commissionsArr = [];
        $coins = [];

        foreach ($commissions as $key => $commission) {
            $coin = $commission->coin;

            if ($coin != 'USDT') {
                $coins[$coin] = $coin;
            }
        }

        foreach ($coins as $key => $coin) {
            $loop = 9999;

            for ($i=0; $i < $loop; $i++) {
                $lastCandle = \App\Allbybitperpetualcoin::orderBy('open_time', 'DESC')->where('coin', $coin)->first();

                if ($lastCandle) {
                    $from = $lastCandle->open_time;
                } else {
                    $from = \App\Commissiontotal::where('coin', $coin)->orderBy('date', 'ASC')->first();
                    $from = strtotime($from->date);

                    // echo $coin . ' ' . date('Y-m-d', $from / 1000).PHP_EOL;
                }

                // $request = sprintf('https://api.bybit.com/spot/quote/v1/kline?symbol=%sUSDT&interval=1d&startTime=%s', $coin, $from);

                $request = sprintf('https://api.bybit.com/public/linear/kline?symbol=%sUSDT&interval=D&limit=200&from=%s', $coin, $from);
                $candles = file_get_contents($request);
                $candles = json_decode($candles);

                foreach ($candles->result as $candle) {
                    $year = date('Y', $candle->open_time);
                    $month = date('m', $candle->open_time);
                    $day = date('d', $candle->open_time);

                    if ($year == 2021 && $month == 12 && $day == 12) {
                        // echo "wrong start and end timestamps"; exit;
                        break 2;
                    }

                    \DB::statement(sprintf('insert ignore into allbybitperpetualcoins (coin, open_time, open_datetime, open, high, low, close, created_at, updated_at) values ("%s", %s, "%s", %s, %s, %s, %s, "%s", "%s")', $coin, $candle->open_time, date('Y-m-d H:i:s', $candle->open_time), $candle->open, $candle->high, $candle->low, $candle->close, date('Y-m-d H:i:s', time()), date('Y-m-d H:i:s', time())));

                    $lastCandle = null;
                    $from = null;

                    // DB::statement(sprintf('insert ignore into %s_%s_candles (timestamp, open, close, high, low, volume, updated_at, created_at) values (%s, %s, %s, %s, %s, %s, "%s", "%s")', $coin, $timeframe, $candle[0], $candle[1], $candle[2], $candle[3], $candle[4], $candle[5], date('Y-m-d H:i:s', time()), date('Y-m-d H:i:s', time())));
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

    public function handle_spot()
    {
        // Scrape All Coins for Spot Trading
        $commissions = \App\Commissiontotal::get();

        $commissionsArr = [];
        $coins = [];

        foreach ($commissions as $key => $commission) {
            $coin = $commission->coin;

            if ($coin != 'USDT') {
                $coins[$coin] = $coin;
            }
        }

        unset($coins['CRO']);
        unset($coins['ETC']);
        unset($coins['LIDO']);
        unset($coins['CRAFT']);

        // $coins = [
        //     'BTCZ21' => 'BTCZ21',
        //     'BTCH22' => 'BTCH22',
        //     'BTCM21' => 'BTCM21',
        //     'BTCU21' => 'BTCU21',
        //     'ETHZ21' => 'ETHZ21',
        //     'ETHU21' => 'ETHU21',
        //     'ETHH22' => 'ETHH22',
        // ];

        foreach ($coins as $key => $coin) {
            $loop = 9999;

            for ($i=0; $i < $loop; $i++) {
                $lastCandle = \App\Allbybitcoin::orderBy('open_time', 'DESC')->where('coin', $coin)->first();

                if ($lastCandle) {
                    $from = $lastCandle->open_time;
                } else {
                    $from = \App\Commissiontotal::where('coin', $coin)->orderBy('date', 'ASC')->first();
                    $from = strtotime($from->date) * 1000;

                    // echo $coin . ' ' . date('Y-m-d', $from / 1000).PHP_EOL;
                }

                $request = sprintf('https://api.bybit.com/spot/quote/v1/kline?symbol=%sUSDT&interval=1d&startTime=%s', $coin, $from);
                echo $coin.PHP_EOL;
                $candles = file_get_contents($request);
                $candles = json_decode($candles);

                // \H::pr($candles->result);

                foreach ($candles->result as $candle) {
                    $year = date('Y', $candle[0] / 1000);
                    $month = date('m', $candle[0] / 1000);
                    $day = date('d', $candle[0] / 1000);

                    if ($year == 2021 && $month == 12 && $day == 12) {
                        // echo "wrong start and end timestamps"; exit;
                        break 2;
                    }

                    \DB::statement(sprintf('insert ignore into allbybitcoins (coin, open_time, open_datetime, open, high, low, close, created_at, updated_at) values ("%s", %s, "%s", %s, %s, %s, %s, "%s", "%s")', $coin, $candle[0], date('Y-m-d H:i:s', $candle[0] / 1000), $candle[1], $candle[2], $candle[3], $candle[4], date('Y-m-d H:i:s', time()), date('Y-m-d H:i:s', time())));

                    $lastCandle = null;
                    $from = null;

                    // DB::statement(sprintf('insert ignore into %s_%s_candles (timestamp, open, close, high, low, volume, updated_at, created_at) values (%s, %s, %s, %s, %s, %s, "%s", "%s")', $coin, $timeframe, $candle[0], $candle[1], $candle[2], $candle[3], $candle[4], $candle[5], date('Y-m-d H:i:s', time()), date('Y-m-d H:i:s', time())));
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

    public function scrape_inverse_perpetual()
    {
        // Scrape Inverse Perpetual
        $loop = 9999;

        for ($i=0; $i < $loop; $i++) {
            // sleep(4);
            // $start = 1495216644000;
            // $end = $start + 86400000; // 24 hours

            // $lastCandle = App\Btccandle::orderBy('id', 'desc')->first();

            // $start = $lastCandle->timestamp;

            $lastCandle = \App\Bybit1min::orderBy('open_time', 'DESC')->first();

            $from = $lastCandle->open_time;
            // $from = 1542157200;

            // $request = sprintf('https://api.bitfinex.com/v2/candles/trade:%s:t%sUSD/hist?sort=1&limit=5000&start=%s', $timeframe, $coin, $start, $end);
            $request = sprintf('https://api.bybit.com/v2/public/kline/list?symbol=BTCUSD&interval=1&limit=200&from=%s', $from);

            $candles = file_get_contents($request);
            $candles = json_decode($candles);

            // \H::pr($candles);

            foreach ($candles->result as $candle) {
                $year = date('Y', $candle->open_time);
                $month = date('m', $candle->open_time);
                $day = date('d', $candle->open_time);

                if ($year == 2021 && $month == 11 && $day == 16) {
                    echo "wrong start and end timestamps"; exit;
                }

                \DB::statement(sprintf('insert ignore into bybit1mins (open_time, open_datetime, open, high, low, close, volume, updated_at, created_at) values (%s, "%s", %s, %s, %s, %s, %s, "%s", "%s")', $candle->open_time, date('Y-m-d H:i:s', $candle->open_time), $candle->open, $candle->high, $candle->low, $candle->close, $candle->volume, date('Y-m-d H:i:s', time()), date('Y-m-d H:i:s', time())));

                // DB::statement(sprintf('insert ignore into %s_%s_candles (timestamp, open, close, high, low, volume, updated_at, created_at) values (%s, %s, %s, %s, %s, %s, "%s", "%s")', $coin, $timeframe, $candle[0], $candle[1], $candle[2], $candle[3], $candle[4], $candle[5], date('Y-m-d H:i:s', time()), date('Y-m-d H:i:s', time())));
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
