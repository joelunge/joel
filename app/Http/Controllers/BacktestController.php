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

    public $strategy = 26409;
    public $balance = 10;
    public $dateRange = ['2020-01-01', '2020-12-31'];
    public $printAllCandles = 1;
    public $printLog = 1;
    public $printResults = 1;
    public $limitFee = -0.00025;
    public $marketFee = 0.00075;
    public $avgFundingFee = 1;
    public $tradeSizeLimit = 100000;
    public $direction = 'long';

    public function __construct()
    {
    }

    public function index()
    {
    	$this->initiateBacktest();
    	$this->setStrategy();

    	// FORCE STRATEGY VARIABLES HERE
    	$this->leverage = 25;
        // $this->length = 75
        // $this->fib = 0.000001;
        // $this->diffRequirement = 75
        $this->sl = 0.5;
        $this->tp = 0.5;

        foreach ($this->candles as $candle) {
        	$this->pushArray($candle);
            $this->i++;
            if (count($this->lastCandles) >= $this->length) {
            	$this->shiftArray();

                $this->low = $this->getLow();
                $this->high = $this->getHigh();

                $this->bullFib = $this->getBullFib();

                $this->currentPrice = end($this->lastCandles)['low'];
                $this->currentHigh = end($this->lastCandles)['high'];

                if (($this->currentPrice <= $this->bullFib) && ($this->bullFib != $this->oldFib)) {
                    if ((($this->high-$this->low)/$this->low) > $this->diffRequirement) {
                        if (! $this->inPosition) {
                //         	echo $this->bullFib;
				            // echo ' - ' . $this->low;
			            	// echo ' - TRADE!!!';
				            // echo "<br>";
                        	$this->enterPosition($candle, $this->direction);
                        }
                    }
                }

                if ($this->inPosition) {
                	$this->resultDecrease = 0;
        			$this->resultIncrease = 0;

        			$this->resultDecrease = $this->getResultDecrease($candle);
                    if ($candle['date'] != $this->entryDatetime) {
                        $this->resultIncrease = $this->getResultIncrease($candle);
                    }

                    if ($this->isLoss()) {
                        $this->inPosition = false;

                        $pl = $this->calculateLossPl($candle);

                        $this->balance = $this->balance + $pl;

                        if ($this->printLog) {
                        	$this->logTrade($pl, 'LOSS');
                        }
                        // echo "LOSS <br />";
                        $this->losses++;
                        if ($this->balance < 0) {
                            break;
                        }
                    } elseif ($this->isWin()) {
                        $this->inPosition = false;

                        $pl = $this->calculateWinPl($candle);
                        
                        $this->balance = $this->balance + round($pl, 10);

                        if ($this->printLog) {
                        	$this->logTrade($pl, 'WIN');
                        }

                        // echo "WIN <br />";
                        $this->wins++;
                        if ($this->balance < 0) {
                            exit;
                        }
                    }
                }
                $this->oldFib = $this->bullFib;

                $this->printAllCandles($candle);
            }
        }

        $this->printResults();
    }

    public function initiateBacktest()
    {
 		$this->candles = $this->getCandles();
 		$this->setNecessaryVariables();
    }

    public function setNecessaryVariables()
    {
    	$this->inPosition = false;
        $this->basePrice = false;
        $this->losses = 0;
        $this->wins = 0;
        $this->oldFib = 0;
        $this->entryDatetime = '';
        $this->entryFib = 0;
        $this->entryLow = 0;
        $this->entryHigh = 0;
        $this->lastCandles = [];
        $this->lows = [];
        $this->highs = [];
        $this->i = 0;
        $this->entryFee = $this->limitFee;
	    $this->winningFee = $this->limitFee;
	    $this->losingFee = $this->marketFee;
    }

    public function getCandles()
    {
    	$candles = \App\Btccandle::whereBetween('date', $this->dateRange)->get()->toArray();

    	return $candles;
    }

    public function setStrategy()
    {
    	if ($this->strategy) {
            $backtest = \App\Backtest::where('id', $this->strategy)->take(1)->get();
            $this->leverage = $backtest[0]->leverage;
            $this->length = $backtest[0]->length;
            $this->fib = $backtest[0]->fib;
            $this->diffRequirement = $backtest[0]->diffrequirement;
            $this->sl = $backtest[0]->sl;
            $this->tp = $backtest[0]->tp;
        }
    }

    public function pushArray($candle)
    {
    	array_push($this->lastCandles, $candle);
        array_push($this->lows, $candle['low']);
        array_push($this->highs, $candle['high']);
    }

    public function shiftArray()
    {
    	array_shift($this->lastCandles);
        array_shift($this->lows);
        array_shift($this->highs);
    }

    public function printResults()
    {
    	if (! $this->printResults) {
    		return false;
    	}
    	echo "</table>";
        echo "Length: " . $this->length;
        echo "<br>";
        echo "Fib: " . $this->fib;
        echo "<br>";
        echo "Wins: " . $this->wins;
        echo "<br>";
        echo "Losses: " . $this->losses;
        echo "<br>";
        echo "Diffrequirement: " . $this->diffRequirement;
        echo "<br>";
        echo "Leverage: " . $this->leverage;
        echo "<br>";
        echo "SL: " . $this->sl;
        echo "<br>";
        echo "TP: " . $this->tp;
        echo "<br>";
        echo round($this->balance, 10);
    }

    public function logTrade($pl, $winloss)
    {
    	echo $this->entryDatetime . ' - ';
    	echo $pl . ' - ' .$this->balance;
        echo "<br>";

        echo $winloss;
        echo number_format($this->balance, 10, '.', ',');
        echo "<br>";
    }

    public function getLow()
    {
    	$low = min($this->lows);

    	return $low;
    }

    public function getHigh()
    {
    	// Get all candles after the low and get the highest value from there
    	$highStack = array_keys($this->lows, min($this->lows))[0] + 1;
        $highStackTmp = $highStack;
        $highStackArr = array_slice($this->highs, $highStackTmp);

        // TODO: UNDERSTAND THIS SHIT

        if (! count($highStackArr)) {
            $high = $this->low+1;
        } else {
            $high = max($highStackArr);
        }

        return $high;
    }

    public function enterPosition($candle, $longshort = 'long')
    {
    	if ($candle['close'] > $candle['open']) {
            if ($candle['high'] != $this->high) {
                $this->entryDatetime = $candle['date'];
                $this->entryFib = $this->bullFib;
                $this->entryLow = $this->low;
                $this->entryHigh = $this->high;
                $this->basePrice = $this->bullFib;
                $this->inPosition = true;
                echo $this->bullFib;
                echo ' - ' . $this->low;
            	echo ' - TRADE!!! - ' . $candle['date'];
                echo "<br>";
            }
        } elseif ($candle['close'] < $candle['open']) {
            $this->entryDatetime = $candle['date'];
            $this->entryFib = $this->bullFib;
            $this->entryLow = $this->low;
            $this->entryHigh = $this->high;
            $this->basePrice = $this->bullFib;
            $this->inPosition = true;
            echo $this->bullFib;
            echo ' - ' . $this->low;
        	echo ' - TRADE!!! - ' . $candle['date'];
            echo "<br>";
        }
    }

    public function getTradeSize()
    {
    	$tradeSize = $this->balance * $this->leverage;

        if ($tradeSize > $this->tradeSizeLimit) {
            $tradeSize = $this->tradeSizeLimit;
        }

        return $tradeSize;
    }

    public function calculateLossPl($candle)
    {
    	$tradeSize = $this->getTradeSize();
    	$pl = ((($tradeSize-($tradeSize*$this->entryFee))*(1-($this->sl/100)))-($tradeSize*$this->losingFee)-$tradeSize);

    	$fundingFee = $this->getFundingFee($candle);
        $pl = $pl - $fundingFee;

    	return $pl;
    }

    public function calculateWinPl($candle)
    {
    	$tradeSize = $this->getTradeSize();
    	$pl = ((($tradeSize-($tradeSize*$this->entryFee))*(1+($this->tp/100)))-($tradeSize*$this->winningFee)-$tradeSize);

    	$fundingFee = $this->getFundingFee($candle);
        $pl = $pl - $fundingFee;

    	return $pl;
    }

    public function getFundingFee($candle)
    {
    	if (! $this->avgFundingFee) {
    		return false;
    	}

    	$timeInTrade = strtotime($candle['date']) - strtotime($this->entryDatetime);
        $fundingTimes = floor($timeInTrade / 28800);

        if ($fundingTimes == 0) {
            $fundingTimes = $this->avgFundingFee;
        }
        $fundingFee = $this->getTradeSize() * ($fundingTimes / 1000);

        return $fundingFee;
    }

    public function isLoss()
    {
    	return $this->resultDecrease > $this->sl;
    }

    public function isWin()
    {
    	return $this->resultIncrease > $this->tp;
    }

    public function getBullFib()
    {
		$bullFib = ((($this->high-$this->low)*$this->fib)+$this->low);

		return $bullFib;
    }

    public function getResultIncrease($candle)
    {
    	$resultIncrease = (($candle['high'] - $this->basePrice) / $this->basePrice) * 100;

    	return $resultIncrease;
    }

   	public function getResultDecrease($candle)
   	{
   		$resultDecrease = (($this->basePrice - $candle['low']) / $this->basePrice) * 100;

   		return $resultDecrease;
   	}

   	public function printAllCandles($candle)
   	{
   		if (! $this->printAllCandles) {
   			return false;
   		}
   		echo 'Date: ' . $candle['date'];
        echo " - ";
        echo 'Low: ' . $this->low;
        echo " - ";
        echo 'High: ' . $this->high;
        echo " - ";
        echo 'Fib: ' . $this->bullFib;
        echo "<br>";
   	}
}