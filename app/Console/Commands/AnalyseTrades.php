<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class AnalyseTrades extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'analyse:trades';

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
        $candles = \App\Bybit5min::whereBetween('open_datetime', ['2020-10-20', '2021-02-21'])->get();
        // $candles = \App\Bybit5min::get();

        $dates = [];

        foreach ($candles as $key => $candle) {
            $dates[] = $candle->open_datetime;
        }

        $i = 0;

        $trades = [];

        $count = 0;
        foreach ($dates as $key => $date) {
            if ($i == 23) {
                // echo $date.PHP_EOL;
                $trades[$date] = $date;
                $count = $count +1;

                $i = $i +1;
            }

            if ($i == 24) {
                $i = 0;
            } else {
                $i = $i +1;
            }
        }

        // echo count($trades); exit;

        $inTrade = false;
        $entryPrice = null;
        $targetPrice = null;
        $stopLossPrice = null;

        $wins = 0;
        $losses = 0;

        foreach ($candles as $key => $candle) {
        	if (! $inTrade) {
	    		if (in_array($candle->open_datetime, $trades)) {
					$entryPrice = $candle->open;
					$targetPrice = $candle->open * 1.005;
					$stopLossPrice = $candle->open * 0.995;
					$inTrade = true;
				}
    		}

    		if ($inTrade) {
    			if ($candle->low <= $stopLossPrice) {
    				$losses = $losses + 1;
    				$inTrade = false;

    				echo "LOSS".PHP_EOL;
    			}
    			if ($candle->high >= $targetPrice) {
    				$wins = $wins + 1;
    				$inTrade = false;

    				echo "WIN".PHP_EOL;
    			}
    		}
        }
        echo "WINS: ".$wins.PHP_EOL;
		echo "LOSSES: ".$losses.PHP_EOL;
		echo PHP_EOL;
    }
}
