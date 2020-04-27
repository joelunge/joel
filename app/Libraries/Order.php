<?php

class Order
{
    public static function automaticTarget()
    {
    	$bfx = new \App\Bitfinex(env('BFX_K'), env('BFX_SC'), 'v1');
    	$positions = $bfx->get_positions();
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
    				$price = $position['base']*(1-(2/100));
    			} else {
    				$price = $position['base'] * ((2 / 100) + 1);
    			}

    			$bfx->new_order(strtoupper($position['symbol']), strval(abs($position['amount'])), strval($price), 'bitfinex', $direction, 'limit');
    		}
    	}
    }
}