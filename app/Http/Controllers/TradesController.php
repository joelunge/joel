<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App;
use H;
use Csv;
use DB;
use Request;
use Auth;

class TradesController extends Controller
{
    /**
     * Show the profile for the given user.
     *
     * @param  int  $id
     * @return Response
     */

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function list()
    {
        $userId = Auth::id();
        $this->closedByTimestamp = $this->getClosedBalancesByTimestamp();
        $coins = DB::table('trades')->select('coin')->groupBy('coin')->get()->toArray();
        if (isset($_GET['coin'])) {
            $coins = [];
            $obj = new \StdClass();
            $obj->coin = $_GET['coin'];
            $coins[] = $obj;
        }

        $allTrades = [];
        foreach ($coins as $coin) {
            $trades = $this->getTrades($coin->coin, $userId);
            $trades = $this->getTradesByTrade($trades);
            $trades = $this->setTradeParameters($trades);

            $allTrades[] = $trades;
        }

        $allTrades = array_merge(...$allTrades);
        
        $allTrades = $this->sortByDate($allTrades);

        switch ($_GET['show']) {
            case '10_trades':
                $allTrades = array_slice($allTrades, -10, 10, true);
                break;

            case '7_days':
                foreach ($allTrades as $key => $value) {
                    if ($key < strtotime('-7 days')) {
                        unset($allTrades[$key]);
                    }
                }

            case '30_days':
                foreach ($allTrades as $key => $value) {
                    if ($key < strtotime('-30 days')) {
                        unset($allTrades[$key]);
                    }
                }

            case '3_months':
                foreach ($allTrades as $key => $value) {
                    if ($key < strtotime('-3 months')) {
                        unset($allTrades[$key]);
                    }
                }
        }

        return view('trades.list', ['trades' => $allTrades]);
    }

    private function getTrades($coin, $userId)
    {
    	$trades = App\Trade::where('coin', '=', $coin)
            ->where('user_id', $userId)
            ->excludeExchangeTrades()
			->orderBy('date', 'ASC')
			->get()
            ->toArray();

		return $trades;
    }

    private function getTradesByTrade($trades)
    {
    	$i = 0;
        $amount = 0;
    	$tradesByTrade = [];
    	foreach ($trades as $key => $trade) {
            $tradesByTrade[$i]['trades'][] = $trade;
            $nextKey = $key + 1;

            $amount = $amount + $trade['amount'];

            if ($nextKey <= (count($trades) - 1)) { // not exceeding last trade
                if (
                    number_format($amount, 2) == 0.00 &&
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
            $lastTrade = $trade['trades'][count($trade['trades']) - 1];

            $date = substr($firstTrade['date'], 0, -9);
            $datetime = $firstTrade['date'];
            $coin = str_replace('/USD', '', $firstTrade['coin']);
            $type = $this->isLongOrShort($firstTrade['amount']);
            $balance = round($this->getClosingBalance($trade));
            $result = $this->getResult($trade, $type);
            $gain = $this->getResult($trade, $type);
            $duration['timestamp'] = strtotime($lastTrade['date']) - strtotime($firstTrade['date']);
            $duration['his'] = gmdate('H:i:s', $duration['timestamp']);
            $duration['seconds'] = gmdate('s', $duration['timestamp']);
            $duration['hours'] = gmdate("H", $duration['timestamp']);
            $duration['minutes'] = gmdate("i", $duration['timestamp']);

            $trades[$key]['parameters'] = [
                'bitfinex_id' => $firstTrade['bitfinex_id'],
                'date' => $date,
                'datetime' => $datetime,
                'coin' => $coin,
                'type' => $type,
                'balance' => $balance,
                'result' => $result,
                'gain' => $gain,
                'duration' => $duration,
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
            ->orWhere('description', 'like', '%settlement%')
            ->orderBy('id', 'DESC')
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

    private function sortByDate($trades)
    {
        $tradeByDatetime = [];
        foreach ($trades as $key => $trade) {
            $tradeDate = strtotime($trade['parameters']['datetime']);
            $tradeByDatetime[$tradeDate] = $tradeDate;
        }

        ksort($tradeByDatetime);

        foreach ($trades as $key => $trade) {
            $tradeByDatetime[strtotime($trade['parameters']['datetime'])] = $trade;
        }

        return $tradeByDatetime;
    }

    public function import()
    {
        return view('import');
    }

    public function upload(Request $request)
    {
        $request = Request::instance();
        foreach ($request->all()['files'] as $file) {
            $filepath = $file->path();
            $filename = $file->getClientOriginalName();
            Csv::uploadCsv($filepath, $filename);
        }
        
        return redirect('trades/import');
    }

    public function edit($bitfinex_id)
    {
        $trade = App\Trade::where('bitfinex_id', $bitfinex_id)->take(1)->get()->toArray();
        $trade = $trade[0];

        $trade['parameters']['date'] = substr($trade['date'], 0, -9);
        $trade['parameters']['datetime'] = $trade['date'];
        $trade['parameters']['coin'] = str_replace('/USD', '', $trade['coin']);
        $trade['parameters']['type'] = $this->isLongOrShort($trade['amount']);

        return view('trades.edit', ['trade' => $trade]);
    }

    public function update(Request $request, $bitfinex_id)
    {
        $request = Request::instance();
        $comment = $request->request->get('comment');

        $trade = App\Trade::where('bitfinex_id', $bitfinex_id)->get();
        $trade = $trade[0];
        $trade->comment = $comment;
        $trade->save();

        return redirect(sprintf('/trades/edit/%s', $bitfinex_id));
    }
}