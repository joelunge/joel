<?php

class Tickers
{
    public static function get($volumeRequirement = 0)
    {
    	$tickersUrl = 'https://api-pub.bitfinex.com/v2/tickers?symbols=ALL';
    	$tickersArr = file_get_contents($tickersUrl);
    	$tickersArr = json_decode($tickersArr);

    	$tickersTmp = [];
    	foreach ($tickersArr as $key => $t) {
    		$ticker = new \StdClass;
    		$ticker->ticker = $t[0];
            $ticker->dailyChange = $t[6];
    		$ticker->lastPrice = $t[7];
    		$ticker->volume = $t[8] * $ticker->lastPrice;
    		$ticker->high = $t[9];
    		$ticker->low = $t[10];
    		$tickersTmp[] = $ticker;
    	}

    	$tickers = [];
    	foreach ($tickersTmp as $key => $t) {
    		$condition1 = strpos($t->ticker, 'USD') !== false;
    		$condition2 = strpos($t->ticker, 'UST') === false;
    		$condition3 = $t->volume > $volumeRequirement;

    		if ($condition1 and $condition2 and $condition3) {
			    $tickers[$t->ticker] = $t;
			}
    	}

    	return $tickers;
    }
}