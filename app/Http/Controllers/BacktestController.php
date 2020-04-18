<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App;
use H;
use Csv;
use DB;
use Request;
use Auth;

class BacktestController extends Controller
{
    /**
     * Show the profile for the given user.
     *
     * @param  int  $id
     * @return Response
     */

    public $lastTrade = 0;
    public $isInTrade = false;
    public $trades = [];
    public $balance = 10000;
    public $indicators = [];

    public function __construct()
    {

        $this->middleware('auth');
    }

    public function index()
    {
        // $this->testing();
        // exit;
        $startingBalance = $this->balance;
        $this->strategy = 'strategy2';
        $candles = $this->getCandles(['2018-01-01 00:00:00', '2018-01-31 23:59:59']);
        $volumes = $this->getVolumes($candles);

        foreach ($candles as $key => $c) {
            if (! $this->isInTrade && $this->shouldEnterTrade($c, $volumes, $this->lastTrade)) {
                $this->enterTrade($c);

                $this->lastTrade = strtotime($c->date);
            }

            if ($this->isInTrade && $this->shouldExitTrade($c)) {
                $this->exitTrade($c);
            }

            // $this->saveIndicators($c);
        }

        // H::pr($this->indicators);
        $resultPercentage = (($this->balance - $startingBalance) / $startingBalance) * 100;

        return view('backtest', ['trades' => $this->trades, 'resultPercentage' => $resultPercentage, 'balance' => $this->balance]);
    }

    public function saveIndicators($c)
    {
        // $this->saveIndicator($c, 'volumeLast24Hours', config('times.hoursToSeconds')[24]);
        // $this->saveIndicator($c, 'volumeLast2Days', config('times.hoursToSeconds')[24] * 2);
        // $this->saveIndicator($c, 'volumeLast3Days', config('times.hoursToSeconds')[24] * 3);
        // $this->saveIndicator($c, 'volumeLast4Days', config('times.hoursToSeconds')[24] * 4);
        // $this->saveIndicator($c, 'volumeLast5Days', config('times.hoursToSeconds')[24] * 5);
        // $this->saveIndicator($c, 'volumeLast6Days', config('times.hoursToSeconds')[24] * 6);
        $this->saveIndicator($c, 'volumeLast7Days', config('times.hoursToSeconds')[24] * 7);
        // $this->saveIndicator($c, 'volumeLast14Days', config('times.hoursToSeconds')[24] * 14);
        // $this->saveIndicator($c, 'volumeLast30Days', config('times.hoursToSeconds')[24] * 30);
        // $this->saveIndicator($c, 'volumeLast60Days', config('times.hoursToSeconds')[24] * 60);
        // $this->saveIndicator($c, 'volumeLast90Days', config('times.hoursToSeconds')[24] * 90);
        // $this->saveIndicator($c, 'volumeLast180Days', config('times.hoursToSeconds')[24] * 180);
        // $this->saveIndicator($c, 'volumeLast365Days', config('times.hoursToSeconds')[24] * 365);
    }

    public function saveIndicator($c, $name, $seconds)
    {
        $this->indicators[$name][] = ['date' => $c->date, 'volume' => $c->volumeUsd];

        $first = strtotime($this->indicators[$name][0]['date']);
        $last = strtotime($this->indicators[$name][count($this->indicators[$name])-1]['date']);

        if ($last - $first > $seconds) {
            array_shift($this->indicators[$name]);
        }
    }

    public function getCandles($dateRange)
    {
        $candles = App\Btccandle::whereBetween('date', $dateRange)->get();

        return $candles;
    }

    public function getVolumes($candles)
    {
        $volumes = [];
        foreach ($candles as $key => $c) {
            $volumes[strtotime($c->date)] = $c->volume;
        }

        return $volumes;
    }

    public function shouldEnterTrade($c, $volumes, $lastTrade)
    {
        if ($this->isInTrade) {
            return false;
        }

        if (strpos($c->date, '2018-01-01') !== false) {
            return false;
        }

        $strategy = $this->strategy;
        return $this->$strategy($c, $volumes, $lastTrade);
    }

    public function strategy1($c, $volumes, $lastTrade)
    {
        $maxVolumeLast24Hours = max(array_column($this->indicators['volumeLast7Days'], 'volume'));

        $volumeDiff = $c->volumeUsd / $maxVolumeLast24Hours;

        if (($c->volumeUsd > $maxVolumeLast24Hours) && $volumeDiff > 1) {
            $this->volumeDiff = $volumeDiff;
            return true;
        }
    }

    // public function strategy1($c, $volumes, $lastTrade)
    // {
    //     $fromTime = strtotime($c->date) - 86400;
    //     $toTime = strtotime($c->date) - 1;

    //     $volumesLast24Hours = (array_filter($volumes, function($k) use ($fromTime, $toTime) {
    //         return $k > $fromTime && $k < ($toTime);
    //     }, ARRAY_FILTER_USE_KEY));

    //     if ($volumesLast24Hours) {
    //         $maxVolumeLast24Hours = max($volumesLast24Hours);
    //     } else {
    //         $maxVolumeLast24Hours = 0;
    //     }
    //     if ($c->volume > $maxVolumeLast24Hours) {
    //         if (strtotime($c->date) - $lastTrade > 86400) {
    //             return true;
    //         }
    //     }

    //     return false;
    // }

    public function strategy2($c, $volumes, $lastTrade)
    {
        // $condition1 = $c->rsi_1m >= 70;
        $condition2 = $c->close > $c->open;
        $condition3 = $c->volumeUsd > 5000000;
        $condition4 = (strtotime($c->date) - $this->lastTrade) > 172800;
        $condition5 = ((($c->close - $c->open) / $c->open) * 100 > 0.4);
        $condition6 = $c->changedPrice > 200;

        // if (($condition1 || $condition2) && $condition3) {
        if ($condition2 && $condition3 && $condition4 && $condition6) {
            $this->volumeDiff = 0;

            // echo strtotime($c->date);
            // echo "<br>";
            // echo $this->lastTrade;
            // echo "<br>";
            // echo "<br>";


            $priceDiff = $this->getPriceDiff($c, 1);
            $this->priceDiffUp = round($priceDiff['up']);
            $this->priceDiffDown = round($priceDiff['down']);

            // if ($this->priceDiffDown > 5) {
            //     return false;
            // }

            // if ($this->priceDiffUp > 5) {
            //     return false;
            // }

            return true;
        }

        return false;
    }

    public function strategy5($c, $volumes, $lastTrade)
    {
        // if ((($c->close - $c->open) / $c->open) * 100 > 1 && $c->volume > 10000000) {
        if ($c->changedPriceUnique > 50 && $c->volumeUsd > 2000000) {
            return true;
        }
    }

    public function strategy4($c, $volume, $lastTrade)
    {
        if ((($c->close - $c->open) / $c->open) * 100 > 2 && $c->rsi_1h > 27 && $c->buyVolume / $c->sellVolume > 7) {
            return true;
        }
    }

    public function strategy3($c, $volumes, $lastTrade)
    {
        $fromTime = strtotime($c->date) - 86400;
        $toTime = strtotime($c->date) - 1;

        $priceMoveLast24Hours = (array_filter($volumes, function($k) use ($fromTime, $toTime) {
            return $k > $fromTime && $k < ($toTime);
        }, ARRAY_FILTER_USE_KEY));

        if ($volumesLast24Hours) {
            $maxVolumeLast24Hours = max($volumesLast24Hours);
        } else {
            $maxVolumeLast24Hours = 0;
        }
        if ($c->volume > $maxVolumeLast24Hours) {
            if (strtotime($c->date) - $lastTrade > 86400) {
                return true;
            }
        }

        return false;
    }

    public function shouldExitTrade($c)
    {
        $condition1 = $this->isInTrade;
        $condition2 = $this->getTradeResults($c) < -1 || $this->getTradeResults($c) > 1;
        if ($condition1 && $condition2) {
            return true;
        }

        return false;
    }

    public function enterTrade($c)
    {
        $this->isInTrade = true;
        $this->tradeData = $c;
        $this->tradeData->direction = $c->close > $c->open ? 'long' : 'short';
        $this->tradeData->priceDiffUp = $this->priceDiffUp;
        $this->tradeData->priceDiffDown = $this->priceDiffDown;
        $this->balance = $this->balance * 0.999;
    }

    public function exitTrade($c)
    {
        $this->trades[$this->tradeData->date] = $this->tradeData;
        $this->trades[$this->tradeData->date]->resultPercentage = $this->getTradeResults($c);
        $this->trades[$this->tradeData->date]->volumeDiff = $this->volumeDiff;
        $this->balance = $this->balance * (($this->getTradeResults($c) / 100) + 1);
        $this->balance = $this->balance * 0.999;
        $this->isInTrade = false;
    }

    public function getTradeResults($c)
    {
        if ($this->tradeData->direction == 'long') {
            $resultPercentage = (($c->close - $this->tradeData->close) / $this->tradeData->close) * 100;
        } else {
            $resultPercentage = (($this->tradeData->close - $c->close) / $c->close) * 100;
        }

        return $resultPercentage;
    }

    public function testing()
    {
        $startingBalance = 3000;
        $entryFee = 0.998;
        $exitFee = 0.999;
        $winPercentage = 1.03;
        $lossPercentage = 0.98;
        $tradesAmount = 12;
        $winrate = 0.90;

        $loserate = 1-$winrate;

        $winsAmount = $tradesAmount * $winrate;
        $lossAmount = $tradesAmount * $loserate;
        $balance = $startingBalance;

        for ($x = 0; $x < round($winsAmount); $x++) {
            $balance = $balance * $entryFee;
            $balance = $balance * $winPercentage;
            $balance = $balance * $exitFee;
        }

        for ($x = 0; $x < round($lossAmount); $x++) {
            $balance = $balance * $entryFee;
            $balance = $balance * $lossPercentage;
            $balance = $balance * $exitFee;
        }

        $balance = $balance - $startingBalance;

        echo round($balance);
        exit;
    }

    public function getPriceDiff($c, $distance = 7)
    {
        $start = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $c->date);
        $end = $start->modify('-'.$distance.' days');

        $model = new App\Btccandle();
        $table_name = $model->getTable();

        $minClose = DB::table($table_name)
            ->select(DB::raw('min(close) as minClose'))
            ->whereBetween('date', [$end->format('Y-m-d H:i:s'), $start->format('Y-m-d H:i:s')])
            ->get()[0]->minClose;

        $maxClose = DB::table($table_name)
            ->select(DB::raw('max(close) as maxClose'))
            ->whereBetween('date', [$end->format('Y-m-d H:i:s'), $start->format('Y-m-d H:i:s')])
            ->get()[0]->maxClose;

        $priceMoveUp = (($c->close-$minClose)/$minClose)*100;
        $priceMoveDown = (($maxClose-$c->close)/$c->close)*100;

        return ['up' => $priceMoveUp, 'down' => $priceMoveDown];
    }
}