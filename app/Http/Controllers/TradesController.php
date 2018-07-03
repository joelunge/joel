<?php

namespace App\Http\Controllers;

use App;
use H;
use App\Http\Controllers\Controller;

class TradesController extends Controller
{
    /**
     * Show the profile for the given user.
     *
     * @param  int  $id
     * @return Response
     */

    public function list()
    {
        $this->closedByTimestamp = $this->getClosedBalancesByTimestamp();
    	$trades = $this->getTrades();
    	$trades = $this->getTradesByTrade($trades);
        $trades = $this->setTradeParameters($trades);

        return view('trades.list', ['trades' => $trades]);
    }

    private function getTrades()
    {
    	$trades = App\Trade::where('date', '>=', '2018-05-01')
			// ->where('coin', 'BTC/USD')
			->orderBy('date', 'ASC')
			->get()
            ->toArray();

		return $trades;
    }

    private function getTradesByTrade($trades)
    {
    	$i = 0;

    	$tradesByTrade = [];
    	foreach ($trades as $key => $trade) {
            $tradesByTrade[$i]['trades'][] = $trade;

            $nextKey = $key + 1;

            if ($nextKey <= (count($trades) - 1)) { // not exceeding last trade
                if (
                    (array_key_exists(strtotime($trade['date']), $this->closedByTimestamp)) && // this trade is closing
                    (! array_key_exists(strtotime($trades[$nextKey]['date']), $this->closedByTimestamp)) // next trade is not closing
                ) {
                    $i++;
                }
            }
		}

		return $tradesByTrade;
    }

    private function setTradeParameters($trades)
    {
        foreach ($trades as $key => $trade) {
            $firstTrade = $trade['trades'][0];

            $date = substr($firstTrade['date'], 0, -9);
            $coin = str_replace('/USD', '', $firstTrade['coin']);
            $type = $this->isLongOrShort($firstTrade['amount']);
            $balance = round($this->getClosingBalance($trade));
            $result = $this->getResult($trade, $type);

            $trades[$key]['parameters'] = [
                'date' => $date,
                'coin' => $coin,
                'type' => $type,
                'balance' => $balance,
                'result' => $result,
            ];
        }

        return $trades;
    }

    private function isLongOrShort($firstOrderAmount)
    { 
        return ($firstOrderAmount > 0) ? 'Long' : 'Short';
    }

    private function getClosingBalance($trade)
    {
        return $this->closedByTimestamp[strtotime($trade['trades'][(count($trade['trades']) - 1)]['date'])]['balance'];
    }

    private function getClosedBalancesByTimestamp()
    {
        $closedBalances = App\Balance::where('description', 'like', '%closed%')
            ->get()
            ->toArray();

        $closedByTimestamp = [];
        foreach ($closedBalances as $key => $closedBalance) {
            $closedByTimestamp[strtotime($closedBalance['date'])]['balance'] = $closedBalance['balance'];
        }

        return $closedByTimestamp;
    }

    private function getResult($trade, $type)
    {
        $result = [];
        $sum = 0; 
        $fees = ['buy' => 0, 'sell' => 0, 'total' => 0];

        foreach ($trade['trades'] as $k => $t) {
            $sum -= ($t['amount'] * $t['price']);
            $fees['total'] -= $t['fee'];
        }

        $result['gross_sum'] = $sum;
        $result['net_sum'] = round($sum - $fees['total']);
        $result['fees'] = $fees;

        return $result;
    }
}