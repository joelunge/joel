<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App;
use Csv;
use DB;
use Request;
use Auth;

class OrdersController extends Controller
{
    /**
     * Show the profile for the given user.
     *
     * @param  int  $id
     * @return Response
     */

    public function __construct()
    {
        // $this->middleware('auth');
    }

    public function new($coin, $amount)
    {
    	return view('orders.new', ['coin' => $coin, 'amount' => $amount]);
    }

    public function edit($coin, $amount, $orderId, $orderType)
    {
    	return view('orders.edit', ['coin' => $coin, 'amount' => $amount, 'order_id' => $orderId, 'order_type' => $orderType]);
    }

    public function sendorder()
    {
    	$bfx = new \App\Bitfinex(env('BFX_K'), env('BFX_SC'), 'v1');
    	$positions = $bfx->get_positions();

    	foreach ($positions as $key => $position) {
    		if ($position['symbol'] == strtolower(request('ticker'))) {
    			$amount = request('amount');
    			$direction = ($amount > 0) ? 'buy' : 'sell';

    			if ($direction == 'buy') {
    				$price = $position['base']*(1-(request('target')/100));
    			} else {
    				$price = $position['base'] * ((request('target') / 100) + 1);
    			}
    			$bfx->new_order(request('ticker'), strval(request('amount')), strval($price), 'bitfinex', $direction, 'limit');
    		}
    	}

        return redirect('/positions');
    }

    public function replaceorder()
    {
    	$bfx = new \App\Bitfinex(env('BFX_K'), env('BFX_SC'), 'v1');
    	$bfx->cancel_order(intval(request('order_id')));
    	$positions = $bfx->get_positions();

    	foreach ($positions as $key => $position) {
    		if ($position['symbol'] == strtolower(request('ticker'))) {
    			$amount = request('amount');
    			$direction = ($amount > 0) ? 'buy' : 'sell';

    			if (request('order_type') == 'limit') {
    				if ($direction == 'buy') {
	    				$price = $position['base']*(1-(request('target')/100));
	    			} else {
	    				$price = $position['base'] * ((request('target') / 100) + 1);
	    			}
    			} else {
    				if ($direction == 'buy') {
    					$price = $position['base'] * ((request('target') / 100) + 1);
	    			} else {
	    				$price = $position['base']*(1-(request('target')/100));
	    			}
    			}
    			
    			$bfx->new_order(request('ticker'), strval(request('amount')), strval($price), 'bitfinex', $direction, request('order_type'));
    		}
    	}

        return redirect('/positions');
    }
}