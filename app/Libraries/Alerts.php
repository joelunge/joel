<?php

class Alerts
{
	public static function alert()
	{
		$tickers = self::getTickers();
		// $rsiAlerts = self::getRsiAlerts($tickers);
		$priceAlerts = self::getPriceAlerts($tickers);
		// $volumeAlerts = self::getVolumeAlerts($tickers);
		// $allAlerts = array_merge($rsiAlerts, $priceAlerts, $volumeAlerts);
		$allAlerts = array_merge($priceAlerts);

		self::sendAlerts($allAlerts);
	}

	private static function getPriceAlerts($tickers)
	{
		$alerts = [];
        $priceAlerts = \App\Alert::all();

        foreach ($priceAlerts as $priceAlert) {
            if (array_key_exists('t' . strtoupper($priceAlert->ticker) . 'USD', $tickers)) {
                $t = $tickers['t' . strtoupper($priceAlert->ticker) . 'USD'];

                $chartUrl = sprintf('https://www.tradingview.com/chart?symbol=BITFINEX%s%sUSD', '%3A', strtoupper($priceAlert->ticker));
                $chartUrl = '<'.$chartUrl.'|CHART>';
                $editUrl = sprintf('<http://crypto.joelunge.site/alerts/edit/%s|EDIT>', $priceAlert->id);
                $deleteUrl = sprintf('<http://crypto.joelunge.site/alerts/delete/%s|DELETE>', $priceAlert->id);
                $icon = ($priceAlert->direction == 'up') ? ':four_leaf_clover:' : ':diamonds:';
                $ticker = str_replace('t', '', str_replace('USD', '', $t->ticker));
                $direction = $priceAlert->direction;
                $comment = $priceAlert->comment ? '- ' . $priceAlert->comment : null;

                $message = '%s %s %s %s - %s %s | %s | %s';

                $notificationDiff = (time() - strtotime($priceAlert->last_notification_sent)) / 60;

                if ($priceAlert->notification_frequency == 0 && $priceAlert->last_notification_sent) {
                	continue;
                }

                if ($notificationDiff < $priceAlert->notification_frequency) {
                    continue;
                }

                if ($priceAlert->direction == 'up' && $t->lastPrice > $priceAlert->price) {
                    $alerts[] = sprintf($message, $icon, $ticker, strtoupper($direction), strtoupper($comment), $priceAlert->price, $chartUrl, $editUrl, $deleteUrl);
                    $priceAlert->last_notification_sent = date("Y-m-d H:i:s");
                    $priceAlert->save();
                } elseif ($priceAlert->direction == 'down' && $t->lastPrice < $priceAlert->price) {
                    $alerts[] = sprintf($message, $icon, $ticker, strtoupper($direction), strtoupper($comment), $priceAlert->price, $chartUrl, $editUrl, $deleteUrl);
                    $priceAlert->last_notification_sent = date("Y-m-d H:i:s");
                    $priceAlert->save();
                }
            }
        }

		return $alerts;
	}

	private static function getVolumeAlerts($tickers)
	{
		$messages = [];
    	foreach ($tickers as $key => $t) {
	    	$candleUrl = sprintf('https://api-pub.bitfinex.com/v2/candles/trade:1m:%s/hist?limit=2', $t->ticker);
	    	$candlesArr = file_get_contents($candleUrl);
	    	$candlesArr = json_decode($candlesArr);
	    	$candlesArr = array_reverse($candlesArr);

	    	$candles = [];
	    	foreach ($candlesArr as $key => $c) {
	    		$candle = new \StdClass;
	    		$candle->id = $key;
	    		$candle->timestamp = $c[0];
	    		$candle->date = date('Y-m-d H:i:s', $candle->timestamp / 1000);
	    		$candle->open = $c[1];
	    		$candle->close = $c[2];
	    		$candle->high = $c[3];
	    		$candle->low = $c[4];
	    		$candle->volume = $c[5];

	    		$candles[] = $candle;
	    	}

	    	foreach ($candles as $key => $c) {
	    		if ($c->volume * $c->close > 1000000) {
	    			$chartUrl = sprintf('https://www.tradingview.com/chart?symbol=BITFINEX%s%sUSD', '%3A', strtoupper(str_replace('t', '', str_replace('USD', '', $t->ticker))));
					$chartUrl = '<'.$chartUrl.'|CHART>';
	    			if ($c->close > $c->open) {
	    				$messages[] = sprintf(':four_leaf_clover: %s - VOLUME %s %s', str_replace('t', '', str_replace('USD', '', $t->ticker)), round($c->volume * $c->close), $chartUrl);
	    			}

	    			if ($c->close < $c->open) {
	    				$messages[] = sprintf(':diamonds: %s VOLUME - %s %s', str_replace('t', '', str_replace('USD', '', $t->ticker)), round($c->volume * $c->close), $chartUrl);	
	    			}
	    		}
	    	}
	    }

	    return $messages;
	}

	private static function getTickers()
	{
		return \Tickers::get(500000);
	}

    private static function getRsiAlerts($tickers)
    {
    	$messages = [];
    	foreach ($tickers as $key => $t) {
	    	$candleUrl = sprintf('https://api-pub.bitfinex.com/v2/candles/trade:15m:%s/hist?limit=5000', $t->ticker);
	    	$candlesArr = file_get_contents($candleUrl);
	    	$candlesArr = json_decode($candlesArr);
	    	$candlesArr = array_reverse($candlesArr);

	    	$candles = [];
	    	foreach ($candlesArr as $key => $c) {
	    		$candle = new \StdClass;
	    		$candle->id = $key;
	    		$candle->timestamp = $c[0];
	    		$candle->date = date('Y-m-d H:i:s', $candle->timestamp / 1000);
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

	        if (date('i') == 59 || date('i') == 14 || date('i') == 29 || date('i') == 44) {
	        	if ($candles[count($candles)-1]->rsi >= 70) {
	    			$messages[] = ':four_leaf_clover: ' . date('H:i', $c->timestamp / 1000) . ' - ' . str_replace('t', '', str_replace('USD', '', $t->ticker)) .' - RSI 70+ | '  . round((1-($t->low/$c->close))*100, 1).'% - ' . $c->high;
	    		}

	    		if ($candles[count($candles)-1]->rsi <= 30) {
	    			$messages[] = ':diamonds: ' . date('H:i', $c->timestamp / 1000) . ' - ' . str_replace('t', '', str_replace('USD', '', $t->ticker)) .' - RSI 30- | '  . round((1-($t->high/$c->close))*100, 1).'% - ' . $c->low;	
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
		        			$priceAtLowRsiCheckpoint1 = $c->low;
		        		}
		        	}

		        	elseif ($c->rsi < $lowRsiCheckpoint1) {
		        		$lowRsiCheckpoint1 = $c->rsi;
		        		$priceAtLowRsiCheckpoint1 = $c->low;
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
		        		} else {
		        			$lowRsiCheckpoint2 = $c->rsi;
		        			$priceAtLowRsiCheckpoint2 = $c->close;

		        			if (($lowRsiCheckpoint2 > $lowRsiCheckpoint1) && ($priceAtLowRsiCheckpoint2 < $priceAtLowRsiCheckpoint1)) {

		        				$condition1 = (((time() * 1000 - (int)$c->timestamp) / 1000) / 60) <= 16;

		        				if ($condition1) {
		        					// UNCOMMENT BELOW TO ENABLE DIVERGENCE ALERTS
		        					// $messages[] = ':four_leaf_clover: ' . date('H:i', $c->timestamp / 1000) . ' - ' . str_replace('t', '', str_replace('USD', '', $t->ticker)) .' - BULLISH DIVERGENCE | '  . round((1-($t->high/$c->close))*100, 1).'%';
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
		        			$priceAtHighRsiCheckpoint1 = $c->high;
		        		}
		        	}

		        	elseif ($c->rsi > $highRsiCheckpoint1) {
		        		$highRsiCheckpoint1 = $c->rsi;
		        		$priceAtHighRsiCheckpoint1 = $c->high;
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
		        		} else {
		        			$highRsiCheckpoint2 = $c->rsi;
		        			$priceAtHighRsiCheckpoint2 = $c->close;

		        			if (($highRsiCheckpoint2 < $highRsiCheckpoint1) && ($priceAtHighRsiCheckpoint2 > $priceAtHighRsiCheckpoint1)) {

		        				$condition1 = (((time() * 1000 - (int)$c->timestamp) / 1000) / 60) <= 16;
		        				if ($condition1) {
		        					// UNCOMMENT BELOW TO ENABLE DIVERGENCE ALERTS
		        					// $messages[] = ':diamonds: ' . date('H:i', $c->timestamp / 1000) . ' - ' . str_replace('t', '', str_replace('USD', '', $t->ticker)) .' - BEARISH DIVERGENCE | +' . round((1-($t->low/$c->close))*100, 1).'%';
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

		return $messages;
    }

    private static function sendAlerts($alerts)
    {
  //   	if (count($alerts) >= 1) {
		// 	\Notifications::slackMessage('======================');
		// }
		foreach ($alerts as $key => $m) {
			\Notifications::slackMessage($m);
		}
    }
}