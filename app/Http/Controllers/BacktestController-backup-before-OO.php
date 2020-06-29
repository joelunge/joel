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
    public $balance = 200;
    public $leverage = 75;
    public $stop = 0.4;
    public $target = 0.5;
    public $indicators = [];

    public function __construct()
    {

        // $this->middleware('auth');
    }

    // INDEX SHORT!!!!!!!
    public function index()
    {
        // $strategy = 148224;
        // $strategy = 3175;
        // $strategy = 63803;
        // $strategy = 39577;
        // $strategy = 35898;
        // $strategy = 3175;
        // $strategy = 87860;
        // $strategy = 26409;
        $strategy = 26409;

        $array = [
            'lengthArr' => [5, 10, 15, 20, 25, 30, 35, 40, 45, 50, 60, 70, 80, 90, 100, 150, 200, 250, 300, 350, 400, 450, 500, 700, 1000, 1500, 2000],
            'fibArr' => [0.05, 0.5, 0.214, 0.382, 0.67, 0.7, 0.764],
            // 'diffRequirementArr' => [0.001, 0.005, 0.01, 0.02, 0.04, 0.05, 0.1, 0.2],
            'slArr' => [0.1, 0.2, 0.3, 0.4, 0.5, 0.7, 1, 1.5, 2, 2.5, 3, 4, 5],
            'tpArr' => [0.1, 0.2, 0.3, 0.4, 0.5, 0.7, 1, 1.5, 2, 2.5, 3, 4, 5],
            'leverageArr' => [10, 25, 75],
        ];

        $combinations = \Combinations::get_combinations($array);

        // exit;
        // $candles = \App\Btccandle::whereBetween('date', ['2020-01-01', '2020-06-07'])->get()->toArray();
        $candles = \App\Btccandle::whereBetween('date', ['2020-03-26', '2020-12-31'])->get()->toArray();
        // foreach ($combinations as $key => $combination) {
        $balance = 100;
        $inPosition = false;
        $basePrice = false;
        $losses = 0;
        $wins = 0;
        $oldFib = 0;
        $entryDatetime = '';
        $entryFib = 0;
        $entryLow = 0;
        $entryHigh = 0;

        // $leverageArr = [10, 25, 75];
        // shuffle($leverageArr);
        // $leverage = $leverageArr[0];
        // $leverage = $combination['leverageArr'];
        $leverage = 75;

        // $lengthArr = [5, 10, 15, 20, 25, 30, 35, 40, 45, 50, 60, 70, 80, 90, 100, 150, 200, 250, 300, 350, 400, 450, 500, 700, 1000, 1500, 2000];
        // shuffle($lengthArr);
        // $length = $lengthArr[0];
        // $length = $combination['lengthArr'];
        $length = 10;
        // $fibArr = [0.05, 0.5, 0.214, 0.382, 0.67, 0.7, 0.764];
        // shuffle($fibArr);
        // $fib = $fibArr[0];
        // $fib = $combination['fibArr'];
        $fib = 0.214;
        // $diffRequirementArr = [0.001, 0.005, 0.01, 0.02, 0.04, 0.05, 0.1, 0.2];
        // shuffle($diffRequirementArr);
        // $diffRequirement = $diffRequirementArr[0];
        // $diffRequirement = $_GET['diffRequirement'];
        $diffRequirement = 0.005;
        // $slArr = [0.1, 0.2, 0.3, 0.4, 0.5, 0.7, 1, 1.5, 2, 2.5, 3, 4, 5];
        // shuffle($slArr);
        // $sl = $slArr[0];
        // $sl = $combination['slArr'];
        $sl = 5;
        // $tpArr = [0.1, 0.2, 0.3, 0.4, 0.5, 0.7, 1, 1.5, 2, 2.5, 3, 4, 5];
        // shuffle($tpArr);
        // $tp = $tpArr[0];
        // $tp = $combination['tpArr'];
        $tp = 1;

        if ($strategy) {
            $backtest = \App\Backtest::where('id', $strategy)->take(1)->get();
            $leverage = $backtest[0]->leverage;
            $leverage = 75;
            $length = $backtest[0]->length;
            $fib = $backtest[0]->fib;
            $diffRequirement = $backtest[0]->diffrequirement;
            // $sl = $backtest[0]->sl;
            $sl = 0.0001;
            // $tp = $backtest[0]->tp;
            $tp = 0.62;
            $fib = 0.9;
        }


        $lastCandles = [];
        $lows = [];
        $highs = [];

        $i = 0;
        // echo "<table>";
        foreach ($candles as $key => $value) {
            array_push($lastCandles, $value);
            array_push($lows, $value['low']);
            array_push($highs, $value['high']);

            $i++;
            if (count($lastCandles) >= $length) {
                array_shift($lastCandles);
                array_shift($lows);
                array_shift($highs);

                $low = min($lows);
                $highStack = array_keys($lows, min($lows))[0] + 1;
                $highStackTmp = $highStack;
                $highStackArr = array_slice($highs, $highStackTmp);
                // H::pr($highStackArr, false);
                // echo "<br>";
                if (! count($highStackArr)) {
                    $high = $low+1;
                } else {
                    $high = max($highStackArr);
                }

                $bullFib = ((($high-$low)*$fib)+$low);
                $currentPrice = end($lastCandles)['low'];
                $currentHigh = end($lastCandles)['high'];

                // echo $currentPrice . ' - ' . $bullFib;
                // echo "<br>";

                if (($currentPrice <= $bullFib) && ($bullFib != $oldFib)) {
                    if ((($high-$low)/$low) > $diffRequirement) {
                        if (! $inPosition) {
                            if ($value['close'] > $value['open']) {
                                if ($value['high'] != $high) {
                                    // H::pr(sprintf('Fib: %s - Low: %s - High: %s', $bullFib, $low, $high), false);
                                    // echo sprintf('<tr><td>Fib: %s</td><td>Low: %s</td><td>High: %s</td></tr>', $bullFib, $low, $high);
                                    $entryDatetime = $value['date'];
                                    $entryFib = $bullFib;
                                    $entryLow = $low;
                                    $entryHigh = $high;
                                    $basePrice = $bullFib;
                                    $inPosition = true;
                                    // echo $value['date'] . " - we got a trade guys!";
                                    // echo "<br>";
                                }
                            } elseif ($value['close'] < $value['open']) {
                                $entryDatetime = $value['date'];
                                $entryFib = $bullFib;
                                $entryLow = $low;
                                $entryHigh = $high;
                                $basePrice = $bullFib;
                                $inPosition = true;
                            }
                        }
                        // exit;
                    }
                }

                if ($inPosition) {
                    $resultDecrease = 0;
                    $resultIncrease = 0;
                    if ($value['date'] != $entryDatetime) {
                        $resultDecrease = (($value['high'] - $basePrice) / $basePrice) * 100;
                        $resultIncrease = (($basePrice - $value['low']) / $basePrice) * 100;
                    }

                    // H::pr($resultDecrease, false);
                    if ($resultDecrease > $sl) {
                        $inPosition = false;
                        
                        $tradeSize = $balance * $leverage;

                        if ($tradeSize > 200000) {
                            $tradeSize = 200000;
                        }

                        $pl = ((($tradeSize-($tradeSize*0.00075))*(1-($sl/100)))-($tradeSize*0.00075)-$tradeSize);
                        // $pl = ((($tradeSize-($tradeSize*-0.0000001))*(1-($sl/100)))-($tradeSize*0.0000001)-$tradeSize);
                        // $pl = ((($tradeSize-($tradeSize*-0.00025))*0.95)-($tradeSize*0.00075)-$tradeSize);
                        $timeInTrade = strtotime($value['date']) - strtotime($entryDatetime);
                        $fundingTimes = floor($timeInTrade / 28800);

                        if ($fundingTimes == 0) {
                            $fundingTimes = 0.3;
                        }
                        $fundingFee = $tradeSize * ($fundingTimes / 1000);

                        // $pl = $pl - $fundingFee;

                        $balance = $balance + round($pl, 10);

                        // echo sprintf('<tr><td width="200">%s</td><td width="150">Fib: %s</td><td width="100">%s</td><td width="150">Low: %s</td><td width="150">High: %s</td></tr>', $entryDatetime, $entryFib, 'loss', $entryLow, $entryHigh);

                        echo $pl . ' - ' .$balance;
                        echo "<br>";

                        echo "LOSS";
                        echo number_format($balance, 10, '.', ',');
                        echo "<br>";
                        $losses++;
                        if ($balance < 0) {
                            break;
                        }
                    } elseif ($resultIncrease > $tp) {
                        $inPosition = false;

                        $tradeSize = $balance * $leverage;

                        if ($tradeSize > 200000) {
                            $tradeSize = 200000;
                        }

                        $pl = ((($tradeSize-($tradeSize*0.00075))*(1+($tp/100)))-($tradeSize*-0.00025)-$tradeSize);
                        // $pl = ((($tradeSize-($tradeSize*-0.0000001))*(1+($tp/100)))-($tradeSize*-0.0000001)-$tradeSize);
                        $timeInTrade = strtotime($value['date']) - strtotime($entryDatetime);
                        $fundingTimes = floor($timeInTrade / 28800);

                        if ($fundingTimes == 0) {
                            $fundingTimes = 0.3;
                        }
                        $fundingFee = $tradeSize * ($fundingTimes / 1000);

                        // $pl = $pl - $fundingFee;
                        
                        $balance = $balance + round($pl, 10);

                        // echo sprintf('<tr><td width="200">%s</td><td width="150">Fib: %s</td><td>%s</td><td width="150">Low: %s</td><td width="150">High: %s</td></tr>', $entryDatetime, $entryFib, 'win', $entryLow, $entryHigh);

                        echo $pl . ' - ' .$balance;
                        echo "<br>";

                        echo "WIN - ";
                        echo number_format($balance, 10, '.', ',');
                        echo "<br>";
                        $wins++;
                        if ($balance < 0) {
                            break;
                        }
                    }
                }
                // if ($balance != 10) {
                //     exit;
                // }
                $oldFib = $bullFib;
            }
        }

        // \DB::statement(sprintf('insert into backtests (leverage, length, fib, wins, losses, diffrequirement, sl, tp, balance, updated_at, created_at) values (%s, %s, %s, %s, %s, %s, %s, %s, %s, "%s", "%s")', $leverage, $length, $fib, $wins, $losses, $diffRequirement, $sl, $tp, number_format($balance, 1000, '.', ''), date('Y-m-d H:i:s', time()), date('Y-m-d H:i:s', time())));

        echo "</table>";
        echo "Length: " . $length;
        echo "<br>";
        echo "Fib: " . $fib;
        echo "<br>";
        echo "Wins: " . $wins;
        echo "<br>";
        echo "Losses: " . $losses;
        echo "<br>";
        echo "Diffrequirement: " . $diffRequirement;
        echo "<br>";
        echo "SL: " . $sl;
        echo "<br>";
        echo "TP: " . $tp;
        echo "<br>";
        // echo number_format($balance, 1000, '.', ',');
        echo round($balance);
        // }
    }

    public function indexBULL()
    // public function index()
    {
        // $strategy = 148224;
        // $strategy = 3175;
        // $strategy = 63803;
        // $strategy = 39577;
        // $strategy = 35898;
        // $strategy = 3175;
        // $strategy = 87860;
        $strategy = 26409;

        $array = [
            'lengthArr' => [5, 10, 15, 20, 25, 30, 35, 40, 45, 50, 60, 70, 80, 90, 100, 150, 200, 250, 300, 350, 400, 450, 500, 700, 1000, 1500, 2000],
            'fibArr' => [0.05, 0.5, 0.214, 0.382, 0.67, 0.7, 0.764],
            // 'diffRequirementArr' => [0.001, 0.005, 0.01, 0.02, 0.04, 0.05, 0.1, 0.2],
            'slArr' => [0.1, 0.2, 0.3, 0.4, 0.5, 0.7, 1, 1.5, 2, 2.5, 3, 4, 5],
            'tpArr' => [0.1, 0.2, 0.3, 0.4, 0.5, 0.7, 1, 1.5, 2, 2.5, 3, 4, 5],
            'leverageArr' => [10, 25, 75],
        ];

        $combinations = \Combinations::get_combinations($array);

        // exit;
        // $candles = \App\Btccandle::whereBetween('date', ['2020-01-01', '2020-06-07'])->get()->toArray();
        $candles = \App\Btccandle::where('date', '>', '2020-01-01')->get()->toArray();
        // foreach ($combinations as $key => $combination) {
        $balance = 10;
        $inPosition = false;
        $basePrice = false;
        $losses = 0;
        $wins = 0;
        $oldFib = 0;
        $entryDatetime = '';
        $entryFib = 0;
        $entryLow = 0;
        $entryHigh = 0;

        // $leverageArr = [10, 25, 75];
        // shuffle($leverageArr);
        // $leverage = $leverageArr[0];
        // $leverage = $combination['leverageArr'];
        $leverage = 75;

        // $lengthArr = [5, 10, 15, 20, 25, 30, 35, 40, 45, 50, 60, 70, 80, 90, 100, 150, 200, 250, 300, 350, 400, 450, 500, 700, 1000, 1500, 2000];
        // shuffle($lengthArr);
        // $length = $lengthArr[0];
        // $length = $combination['lengthArr'];
        $length = 10;
        // $fibArr = [0.05, 0.5, 0.214, 0.382, 0.67, 0.7, 0.764];
        // shuffle($fibArr);
        // $fib = $fibArr[0];
        // $fib = $combination['fibArr'];
        $fib = 0.214;
        // $diffRequirementArr = [0.001, 0.005, 0.01, 0.02, 0.04, 0.05, 0.1, 0.2];
        // shuffle($diffRequirementArr);
        // $diffRequirement = $diffRequirementArr[0];
        // $diffRequirement = $_GET['diffRequirement'];
        $diffRequirement = 0.005;
        // $slArr = [0.1, 0.2, 0.3, 0.4, 0.5, 0.7, 1, 1.5, 2, 2.5, 3, 4, 5];
        // shuffle($slArr);
        // $sl = $slArr[0];
        // $sl = $combination['slArr'];
        $sl = 5;
        // $tpArr = [0.1, 0.2, 0.3, 0.4, 0.5, 0.7, 1, 1.5, 2, 2.5, 3, 4, 5];
        // shuffle($tpArr);
        // $tp = $tpArr[0];
        // $tp = $combination['tpArr'];
        $tp = 1;

        if ($strategy) {
            $backtest = \App\Backtest::where('id', $strategy)->take(1)->get();
            $leverage = $backtest[0]->leverage;
            // $leverage = 10;
            $length = $backtest[0]->length;
            // $length = 3;
            $fib = $backtest[0]->fib;
            $fib = 0.9;
            $diffRequirement = $backtest[0]->diffrequirement;
            $sl = $backtest[0]->sl;
            $tp = $backtest[0]->tp;
        }


        $lastCandles = [];
        $lows = [];
        $highs = [];

        $i = 0;
        // echo "<table>";
        foreach ($candles as $key => $value) {
            array_push($lastCandles, $value);
            array_push($lows, $value['low']);
            array_push($highs, $value['high']);

            $i++;
            if (count($lastCandles) >= $length) {
                array_shift($lastCandles);
                array_shift($lows);
                array_shift($highs);

                $low = min($lows);
                $highStack = array_keys($lows, min($lows))[0] + 1;
                $highStackTmp = $highStack;
                $highStackArr = array_slice($highs, $highStackTmp);
                // H::pr($highStackArr, false);
                // echo "<br>";
                if (! count($highStackArr)) {
                    $high = $low+1;
                } else {
                    $high = max($highStackArr);
                }

                $bullFib = ((($high-$low)*$fib)+$low);

                $currentPrice = end($lastCandles)['low'];
                $currentHigh = end($lastCandles)['high'];

                // echo $currentPrice . ' - ' . $bullFib;
                // echo "<br>";

                if (($currentPrice <= $bullFib) && ($bullFib != $oldFib)) {
                    if ((($high-$low)/$low) > $diffRequirement) {
                        if (! $inPosition) {
                            if ($value['close'] > $value['open']) {
                                if ($value['high'] != $high) {
                                    // H::pr(sprintf('Fib: %s - Low: %s - High: %s', $bullFib, $low, $high), false);
                                    // echo sprintf('<tr><td>Fib: %s</td><td>Low: %s</td><td>High: %s</td></tr>', $bullFib, $low, $high);
                                    $entryDatetime = $value['date'];
                                    $entryFib = $bullFib;
                                    $entryLow = $low;
                                    $entryHigh = $high;
                                    $basePrice = $bullFib;
                                    $inPosition = true;
                                    // echo $value['date'] . " - we got a trade guys!";
                                    // echo "<br>";
                                }
                            } elseif ($value['close'] < $value['open']) {
                                $entryDatetime = $value['date'];
                                $entryFib = $bullFib;
                                $entryLow = $low;
                                $entryHigh = $high;
                                $basePrice = $bullFib;
                                $inPosition = true;
                            }
                        }
                        // exit;
                    }
                }

                if ($inPosition) {
                    $resultDecrease = 0;
                    $resultIncrease = 0;
                    if ($value['date'] != $entryDatetime) {
                        $resultIncrease = (($value['high'] - $basePrice) / $basePrice) * 100;
                        $resultDecrease = (($basePrice - $value['low']) / $basePrice) * 100;
                    }

                    // H::pr($resultDecrease, false);
                    if ($resultDecrease > $sl) {
                        $inPosition = false;
                        
                        $tradeSize = $balance * $leverage;

                        if ($tradeSize > 100000) {
                            $tradeSize = 100000;
                        }

                        $pl = ((($tradeSize-($tradeSize*-0.00025))*(1-($sl/100)))-($tradeSize*0.00075)-$tradeSize);
                        // $pl = ((($tradeSize-($tradeSize*-0.0000001))*(1-($sl/100)))-($tradeSize*0.0000001)-$tradeSize);
                        // $pl = ((($tradeSize-($tradeSize*-0.00025))*0.95)-($tradeSize*0.00075)-$tradeSize);
                        $timeInTrade = strtotime($value['date']) - strtotime($entryDatetime);
                        $fundingTimes = floor($timeInTrade / 28800);

                        if ($fundingTimes == 0) {
                            $fundingTimes = 0.3;
                        }
                        $fundingFee = $tradeSize * ($fundingTimes / 1000);

                        $pl = $pl - $fundingFee;

                        $balance = $balance + round($pl, 10);

                        // echo sprintf('<tr><td width="200">%s</td><td width="150">Fib: %s</td><td width="100">%s</td><td width="150">Low: %s</td><td width="150">High: %s</td></tr>', $entryDatetime, $entryFib, 'loss', $entryLow, $entryHigh);

                        echo $pl . ' - ' .$balance;
                        echo "<br>";

                        echo "LOSS";
                        echo number_format($balance, 10, '.', ',');
                        echo "<br>";
                        $losses++;
                        if ($balance < 0) {
                            break;
                        }
                    } elseif ($resultIncrease > $tp) {
                        $inPosition = false;

                        $tradeSize = $balance * $leverage;

                        if ($tradeSize > 100000) {
                            $tradeSize = 100000;
                        }

                        $pl = ((($tradeSize-($tradeSize*-0.00025))*(1+($tp/100)))-($tradeSize*-0.00025)-$tradeSize);
                        // $pl = ((($tradeSize-($tradeSize*-0.0000001))*(1+($tp/100)))-($tradeSize*-0.0000001)-$tradeSize);
                        $timeInTrade = strtotime($value['date']) - strtotime($entryDatetime);
                        $fundingTimes = floor($timeInTrade / 28800);

                        if ($fundingTimes == 0) {
                            $fundingTimes = 0.3;
                        }
                        $fundingFee = $tradeSize * ($fundingTimes / 1000);

                        $pl = $pl - $fundingFee;
                        
                        $balance = $balance + round($pl, 10);

                        // echo sprintf('<tr><td width="200">%s</td><td width="150">Fib: %s</td><td>%s</td><td width="150">Low: %s</td><td width="150">High: %s</td></tr>', $entryDatetime, $entryFib, 'win', $entryLow, $entryHigh);

                        echo $pl . ' - ' .$balance;
                        echo "<br>";

                        echo "WIN - ";
                        echo number_format($balance, 10, '.', ',');
                        echo "<br>";
                        $wins++;
                        if ($balance < 0) {
                            break;
                        }
                    }
                }
                // if ($balance != 10) {
                //     exit;
                // }
                $oldFib = $bullFib;
            }
        }

        // \DB::statement(sprintf('insert into backtests (leverage, length, fib, wins, losses, diffrequirement, sl, tp, balance, updated_at, created_at) values (%s, %s, %s, %s, %s, %s, %s, %s, %s, "%s", "%s")', $leverage, $length, $fib, $wins, $losses, $diffRequirement, $sl, $tp, number_format($balance, 1000, '.', ''), date('Y-m-d H:i:s', time()), date('Y-m-d H:i:s', time())));

        echo "</table>";
        echo "Length: " . $length;
        echo "<br>";
        echo "Fib: " . $fib;
        echo "<br>";
        echo "Wins: " . $wins;
        echo "<br>";
        echo "Losses: " . $losses;
        echo "<br>";
        echo "Diffrequirement: " . $diffRequirement;
        echo "<br>";
        echo "SL: " . $sl;
        echo "<br>";
        echo "TP: " . $tp;
        echo "<br>";
        // echo number_format($balance, 1000, '.', ',');
        echo round($balance);
        // }
    }

    public function indexUseThisOne()
    {
        $balance = 10;
        $leverage = 75;
        $inPosition = false;
        $basePrice = false;
        $losses = 0;
        $wins = 0;
        $oldFib = 0;
        $entryDatetime = '';
        $entryFib = 0;
        $entryLow = 0;
        $entryHigh = 0;
    	$candles = \App\Btccandle::where('date', '>', '2020-01-01')->get()->toArray();

    	$lastCandles = [];
    	$lows = [];
    	$highs = [];

    	$i = 0;
        echo "<table>";
    	foreach ($candles as $key => $value) {
    		array_push($lastCandles, $value);
    		array_push($lows, $value['low']);
    		array_push($highs, $value['high']);

    		$i++;
    		if (count($lastCandles) >= 15) {
    			array_shift($lastCandles);
    			array_shift($lows);
    			array_shift($highs);

                $low = min($lows);
                $highStack = array_keys($lows, min($lows))[0] + 1;
                $highStackTmp = $highStack;
                $highStackArr = array_slice($highs, $highStackTmp);
                // H::pr($highStackArr, false);
                // echo "<br>";
                if (! count($highStackArr)) {
                    $high = $low+1;
                } else {
                    $high = max($highStackArr);
                }

                $bullFib = ((($high-$low)*0.382)+$low);
                $currentPrice = end($lastCandles)['low'];
                $currentHigh = end($lastCandles)['high'];

                // echo $currentPrice . ' - ' . $bullFib;
                // echo "<br>";

                if (($currentPrice <= $bullFib) && ($bullFib != $oldFib)) {
                    if ((($high-$low)/$low) > 0.005) {
                        if (! $inPosition) {
                            if ($value['close'] > $value['open']) {
                                if ($value['high'] != $high) {
                                    // H::pr(sprintf('Fib: %s - Low: %s - High: %s', $bullFib, $low, $high), false);
                                    // echo sprintf('<tr><td>Fib: %s</td><td>Low: %s</td><td>High: %s</td></tr>', $bullFib, $low, $high);
                                    // $entryDatetime = $value['date'];
                                    $entryFib = $bullFib;
                                    $entryLow = $low;
                                    $entryHigh = $high;
                                    $basePrice = $bullFib;
                                    $inPosition = true;
                                    // echo $value['date'] . " - we got a trade guys!";
                                    // echo "<br>";
                                }
                            } elseif ($value['close'] < $value['open']) {
                                $entryDatetime = $value['date'];
                                $entryFib = $bullFib;
                                $entryLow = $low;
                                $entryHigh = $high;
                                $basePrice = $bullFib;
                                $inPosition = true;
                            }
                        }
                        // exit;
                    }
                }

                if ($inPosition) {
                    $resultDecrease = 0;
                    $resultIncrease = 0;
                    if ($value['date'] != $entryDatetime) {
                        $resultIncrease = (($value['high'] - $basePrice) / $basePrice) * 100;
                        $resultDecrease = (($basePrice - $value['low']) / $basePrice) * 100;
                    }

                    // H::pr($resultDecrease, false);
                    if ($resultDecrease > 0.4) {
                        $inPosition = false;
                        
                        $tradeSize = $balance * $leverage;
                        $pl = ((($tradeSize-($tradeSize*-0.00025))*0.996)-($tradeSize*0.00075)-$tradeSize);
                        $balance = $balance + $pl;

                        // echo sprintf('<tr><td width="200">%s</td><td width="150">Fib: %s</td><td width="100">%s</td><td width="150">Low: %s</td><td width="150">High: %s</td></tr>', $entryDatetime, $entryFib, 'loss', $entryLow, $entryHigh);

                        // echo "LOSS";
                        // echo "<br>";
                        // echo $balance;
                        // echo "<br>";
                        $losses++;
                    } elseif ($resultIncrease > 0.5) {
                        $inPosition = false;

                        $tradeSize = $balance * $leverage;
                        $pl = ((($tradeSize-($tradeSize*-0.00025))*1.005)-($tradeSize*-0.00025)-$tradeSize);
                        $balance = $balance + $pl;

                        // echo sprintf('<tr><td width="200">%s</td><td width="150">Fib: %s</td><td>%s</td><td width="150">Low: %s</td><td width="150">High: %s</td></tr>', $entryDatetime, $entryFib, 'win', $entryLow, $entryHigh);
                        // echo "WIN";
                        // echo "<br>";
                        // echo $balance;
                        // echo "<br>";
                        $wins++;   
                    }
                }
                // if ($balance != 10) {
                //     exit;
                // }
                $oldFib = $bullFib;
    		}
    	}

        echo "</table>";
        echo "Wins: " . $wins;
        echo "<br>";
        echo "Losses: " . $losses;
        echo "<br>";
        echo number_format($balance, 0, '', '');
    }

    public function index2()
    {
        // $this->testing();
        // exit;
        $startingBalance = $this->balance;
        $this->strategy = 'strategy2';
        $candles = $this->getCandles(['2018-02-01 00:00:00', '2018-02-28 23:59:59']);
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
        $this->balance = $this->balance * 0.99925;
    }

    public function exitTrade($c)
    {
        $this->trades[$this->tradeData->date] = $this->tradeData;
        $this->trades[$this->tradeData->date]->resultPercentage = $this->getTradeResults($c);
        $this->trades[$this->tradeData->date]->volumeDiff = $this->volumeDiff;
        $this->balance = $this->balance * (($this->getTradeResults($c) / 100) + 1);
        $this->balance = $this->balance * 0.99999;
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
        exit;
        $startingBalance = 3000;
        $entryFee = 0.99925;
        $exitFee = 0.99999;
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