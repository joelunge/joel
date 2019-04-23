<?php

class RsiAlerts
{
    public static function alert()
    {
    	$messages = [];
    	$tickersUrl = 'https://api-pub.bitfinex.com/v2/tickers?symbols=ALL';
    	$tickersArr = file_get_contents($tickersUrl);
    	$tickersArr = json_decode($tickersArr);

    	$tickersTmp = [];
    	foreach ($tickersArr as $key => $t) {
    		$ticker = new \StdClass;
    		$ticker->ticker = $t[0];
    		$ticker->lastPrice = $t[7];
    		$ticker->volume = $t[8] * $ticker->lastPrice;
    		$ticker->high = $t[9];
    		$ticker->low = $t[10];
    		$tickersTmp[] = $ticker;
    	}

    	$tickers = [];
    	foreach ($tickersTmp as $key => $t) {
    		$condition1 = strpos($t->ticker, 'USD') !== false;
    		$condition2 = $t->volume > 500000;

    		if ($condition1 and $condition2) {
			    $tickers[] = $t;
			}
    	}

    	foreach ($tickers as $key => $t) {
	    	$candleUrl = sprintf('https://api-pub.bitfinex.com/v2/candles/trade:15m:%s/hist?limit=5000', 'tBTCUSD');
	    	$candlesArr = file_get_contents($candleUrl);
	    	$candlesArr = json_decode($candlesArr);
	    	$candlesArr = array_reverse($candlesArr);

	    	$candles = [];
	    	foreach ($candlesArr as $key => $c) {
	    		$candle = new \StdClass;
	    		$candle->id = $key;
	    		$candle->timestamp = $c[0];
	    		$candle->open = $c[1];
	    		$candle->close = $c[2];
	    		$candle->high = $c[3];
	    		$candle->low = $c[4];
	    		$candle->volume = $c[5];

	    		$candles[] = $candle;
	    	}

	    	// H::pr($candles);

	    	$prices = [];
	        $ids = [];
	        foreach ($candles as $key => $c) {
	            $prices[] = $c->close;
	            $ids[] = $c->id;
	        }

	        $rsis = \Indicators::Rsi($ids, $prices);

	        foreach ($candles as $key => $c) {
	        	if (isset($rsis[$c->id])) {
	        		$c->rsi = $rsis[$c->id];
	        	} else {
	        		$c->rsi = 'x';
	        	}
	        }

	        # BULLISH RSI DIVERGENCE

	        $lowRsiCheckpoint1 = false;
	        $priceAtLowRsiCheckpoint1 = false;
	        $lowRsiCheckpoint2 = false;
	        $priceAtLowRsiCheckpoint2 = false;
	        $condition1 = false;
	        foreach ($candles as $key => $c) {
	        	if ($c->rsi != 'x') {

		        	if (! $lowRsiCheckpoint1 && !$lowRsiCheckpoint2) {
		        		if ($c->rsi <= 30) {
		        			$lowRsiCheckpoint1 = $c->rsi;
		        			$priceAtLowRsiCheckpoint1 = $c->close;
		        		}
		        	}

		        	elseif ($c->rsi < $lowRsiCheckpoint1) {
		        		$lowRsiCheckpoint1 = $c->rsi;
		        		$priceAtLowRsiCheckpoint1 = $c->close;
		        		$lowRsiCheckpoint2 = false;
	        			$priceAtLowRsiCheckpoint2 = false;
		        	}

		        	elseif ($c->rsi >= 70) {
		        		$lowRsiCheckpoint1 = false;
				        $priceAtLowRsiCheckpoint1 = false;
				        $lowRsiCheckpoint2 = false;
				        $priceAtLowRsiCheckpoint2 = false;
		        	}

		        	elseif ($c->rsi > $lowRsiCheckpoint1) {
		        		if (! $lowRsiCheckpoint2) {
		        			// if ($c->rsi > 30) {
		        				$lowRsiCheckpoint2 = $c->rsi;
		        				$priceAtLowRsiCheckpoint2 = $c->close;
		        			// }
		        		} elseif (($c->rsi < $lowRsiCheckpoint2) && ($c->rsi <= 30)) {
		        			$lowRsiCheckpoint2 = $c->rsi;
		        			$priceAtLowRsiCheckpoint2 = $c->close;

		        			if (($lowRsiCheckpoint2 > $lowRsiCheckpoint1) && ($priceAtLowRsiCheckpoint2 < $priceAtLowRsiCheckpoint1)) {
		        				$condition1 = true;
		        				if ((((time() * 1000 - (int)$c->timestamp) / 1000) / 60) <= 16) {
		        					$messages[] = str_replace('t', '', str_replace('USD', '', $t->ticker)) .' - BULLISH DIVERGENCE';
		        				}
		        				$lowRsiCheckpoint1 = false;
						        $priceAtLowRsiCheckpoint1 = false;
						        $lowRsiCheckpoint2 = false;
						        $priceAtLowRsiCheckpoint2 = false;
		        			}
		        		}
		        	}
		        }
			}

			# BEARISH RSI DIVERGENCE

			$highRsiCheckpoint1 = false;
	        $priceAtHighRsiCheckpoint1 = false;
	        $highRsiCheckpoint2 = false;
	        $priceAtHighRsiCheckpoint2 = false;
	        $condition1 = false;
	        foreach ($candles as $key => $c) {
	        	if ($c->rsi != 'x') {

		        	if (! $highRsiCheckpoint1 && !$highRsiCheckpoint2) {
		        		if ($c->rsi >= 70) {
		        			$highRsiCheckpoint1 = $c->rsi;
		        			$priceAtHighRsiCheckpoint1 = $c->close;
		        		}
		        	}

		        	elseif ($c->rsi > $highRsiCheckpoint1) {
		        		$highRsiCheckpoint1 = $c->rsi;
		        		$priceAtHighRsiCheckpoint1 = $c->close;
		        		$highRsiCheckpoint2 = false;
	        			$priceAtHighRsiCheckpoint2 = false;
		        	}

		        	elseif ($c->rsi <= 30) {
		        		$highRsiCheckpoint1 = false;
				        $priceAtHighRsiCheckpoint1 = false;
				        $highRsiCheckpoint2 = false;
				        $priceAtHighRsiCheckpoint2 = false;
		        	}

		        	elseif ($c->rsi < $highRsiCheckpoint1) {
		        		if (! $highRsiCheckpoint2) {
		        			// if ($c->rsi < 70) {
		        				$highRsiCheckpoint2 = $c->rsi;
		        				$priceAtHighRsiCheckpoint2 = $c->close;
		        			// }
		        		} elseif (($c->rsi > $highRsiCheckpoint2) && ($c->rsi >= 70)) {
		        			$highRsiCheckpoint2 = $c->rsi;
		        			$priceAtHighRsiCheckpoint2 = $c->close;

		        			if (($highRsiCheckpoint2 < $highRsiCheckpoint1) && ($priceAtHighRsiCheckpoint2 > $priceAtHighRsiCheckpoint1)) {
		        				$condition1 = true;
		        				if ((((time() * 1000 - (int)$c->timestamp) / 1000) / 60) <= 16) {
		        					$messages[] = str_replace('t', '', str_replace('USD', '', $t->ticker)) .' - BEARISH DIVERGENCE';
		        				}
		        				$highRsiCheckpoint1 = false;
						        $priceAtHighRsiCheckpoint1 = false;
						        $highRsiCheckpoint2 = false;
						        $priceAtHighRsiCheckpoint2 = false;
		        			}
		        		}
		        	}
		        }
			}
		}
		if (count($messages) >= 1) {
			\Notifications::slackMessage('======================');
		}
		foreach ($messages as $key => $m) {
			\Notifications::slackMessage($m);
		}
    }
}