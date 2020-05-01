<?php

class Order
{
    public static function automaticTarget()
    {
    	$bfx = new \App\Bitfinex(env('BFX_K'), env('BFX_SC'), 'v1');
        usleep(rand(1000000, 3000000));
    	$positions = $bfx->get_positions();
        usleep(rand(1000000, 3000000));
    	$orders = $bfx->get_orders();

    	foreach ($positions as $key => $position) {
    		$limitOrder = null;

    		foreach ($orders as $key => $order) {
    			if (($position['symbol'] == $order['symbol']) && $order['type'] == 'limit') {
    				$limitOrder = $order;
    			}
    		}

    		if (! $limitOrder) {
    			$direction = (floatval($position['amount']) < 0) ? 'buy' : 'sell';
    			if ($direction == 'buy') {
    				$price = $position['base'] * (1-(2/100));
    			} else {
    				$price = $position['base'] * ((2 / 100) + 1);
    			}

    			$bfx->new_order(strtoupper($position['symbol']), strval(abs($position['amount'])), strval($price), 'bitfinex', $direction, 'limit');
    		}
    	}
    }

    public static function automaticStop()
    {
        $bfx = new \App\Bitfinex(env('BFX_K'), env('BFX_SC'), 'v1');
        usleep(rand(1000000, 3000000));
        $positions = $bfx->get_positions();
        usleep(rand(1000000, 3000000));
        $orders = $bfx->get_orders();

        foreach ($positions as $key => $position) {
            $stopOrder = null;

            foreach ($orders as $key => $order) {
                if (($position['symbol'] == $order['symbol']) && $order['type'] == 'stop') {
                    $stopOrder = $order;
                }
            }

            if (! $stopOrder) {
                $direction = (floatval($position['amount']) < 0) ? 'buy' : 'sell';
                if ($direction == 'buy') {
                    $price = $position['base'] * ((2 / 100) + 1);
                } else {
                    $price = $position['base'] * (1-(2/100));
                }

                $bfx->new_order(strtoupper($position['symbol']), strval(abs($position['amount'])), strval($price), 'bitfinex', $direction, 'stop');
            }
        }
    }
}