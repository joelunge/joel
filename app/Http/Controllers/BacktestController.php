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

    public function __construct()
    {

        $this->middleware('auth');
    }

    public function index()
    {
        $startingBalance = $this->balance;
        $this->strategy = 'strategy2';
        $candles = $this->getCandles(['2018-01-01 00:00:00', '2018-12-31 23:59:59']);
        $volumes = $this->getVolumes($candles);

        foreach ($candles as $key => $c) {
            if ($this->shouldEnterTrade($c, $volumes, $this->lastTrade)) {
                $this->enterTrade($c);

                $this->lastTrade = strtotime($c->date);
            }

            if ($this->isInTrade && $this->shouldExitTrade($c)) {
                $this->exitTrade($c);
            }
        }

        $resultPercentage = (($this->balance - $startingBalance) / $startingBalance) * 100;

        return view('backtest', ['trades' => $this->trades, 'resultPercentage' => $resultPercentage, 'balance' => $this->balance]);
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
        $fromTime = strtotime($c->date) - 86400;
        $toTime = strtotime($c->date) - 1;

        $volumesLast24Hours = (array_filter($volumes, function($k) use ($fromTime, $toTime) {
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

    public function strategy2($c, $volumes, $lastTrade)
    {
        // $condition1 = $c->rsi_5m >= 85;
        // $condition2 = $c->rsi_5m <= 15;
        $condition3 = $c->volumeUsd > 10000000;

        // if (($condition1 || $condition2) && $condition3) {
        if ($condition3) {
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
        $condition2 = $this->getTradeResults($c) < -2 || $this->getTradeResults($c) > 1;
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
        $this->balance = $this->balance * 0.998;
    }

    public function exitTrade($c)
    {
        $this->trades[$this->tradeData->date] = $this->tradeData;
        $this->trades[$this->tradeData->date]->resultPercentage = $this->getTradeResults($c);
        $this->balance = $this->balance * (($this->getTradeResults($c) / 100) + 1);
        $this->balance = $this->balance * 0.998;
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
}