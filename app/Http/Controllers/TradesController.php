<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App;
use H;
use UsdToSek;
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

    private $usedHashes = [];

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function list()
    {
        $userId = Auth::id();
        $this->closedByTimestamp = $this->getClosedBalancesByTimestamp();
        
        $coins = $this->getCoins();
        $additionalCosts = $this->getAdditionalCosts($userId);

        $allTrades = [];
        foreach ($coins as $coin) {
            $trades = $this->getTrades($coin->coin, $userId);
            $trades = $this->getTradesByTrade($trades);
            $trades = $this->setTradeParameters($trades, $additionalCosts);

            $allTrades[] = $trades;
        }

        $allTrades = array_merge(...$allTrades);
        
        $allTrades = $this->sortByDate($allTrades);

        if (! isset($_GET['show'])) {
            return redirect('/trades?show=10_trades');
        }

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
                break;

            case '30_days':
                foreach ($allTrades as $key => $value) {
                    if ($key < strtotime('-30 days')) {
                        unset($allTrades[$key]);
                    }
                }
                break;

            case '3_months':
                foreach ($allTrades as $key => $value) {
                    if ($key < strtotime('-3 months')) {
                        unset($allTrades[$key]);
                    }
                }
                break;
        }

        $stats = $this->getStats($allTrades, $additionalCosts);

        return view('trades.list', ['trades' => $allTrades, 'stats' => $stats]);
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
                if ($trade['price'] > 1000) {
                    if (round($amount, 2) == 0.00) {
                        if (
                            (array_key_exists(strtotime($trade['date']), $this->closedByTimestamp)) && // this trade is closing
                            (! array_key_exists(strtotime($trades[$nextKey]['date']), $this->closedByTimestamp)) // next trade is not closing
                        ) {
                            $i++;
                        }
                    }
                } else {
                    if (round($amount) == 0) {
                        if (
                            (array_key_exists(strtotime($trade['date']), $this->closedByTimestamp)) && // this trade is closing
                            (! array_key_exists(strtotime($trades[$nextKey]['date']), $this->closedByTimestamp)) // next trade is not closing
                        ) {
                            $i++;
                        }
                    }
                }
            }
        }

        return $tradesByTrade;
    }

    private function setTradeParameters($trades, $additionalCosts)
    {
        foreach ($trades as $key => $trade) {
            $firstTrade = $trade['trades'][0];
            $lastTrade = $trade['trades'][count($trade['trades']) - 1];

            $date = substr($firstTrade['date'], 0, -9);
            $datetime = $firstTrade['date'];
            $coin = str_replace('/USD', '', $firstTrade['coin']);
            $type = $this->isLongOrShort($firstTrade['amount']);
            $balance = $this->getClosingBalance($trade);
            $result = $this->getResult($trade, $additionalCosts);
            $winloss = ($result['net_sum'] > 0 ? 'win' : 'loss');
            $duration['timestamp'] = strtotime($lastTrade['date']) - strtotime($firstTrade['date']);
            $duration['his'] = gmdate('H:i:s', $duration['timestamp']);
            $duration['seconds'] = gmdate('s', $duration['timestamp']);
            $duration['hours'] = gmdate("H", $duration['timestamp']);
            $duration['minutes'] = gmdate("i", $duration['timestamp']);

            $amount = $this->getTotalAmount($trade);

            $trades[$key]['parameters'] = [
                'bitfinex_id' => $firstTrade['bitfinex_id'],
                'date' => $date,
                'datetime' => $datetime,
                'coin' => $coin,
                'entry_price' => $firstTrade['price'],
                'exit_price' => $lastTrade['price'],
                'type' => $type,
                'balance' => $balance,
                'amount' => $amount,
                'result' => $result,
                'winloss' => $winloss,
                'start_time' => $firstTrade['date'],
                'end_time' => $lastTrade['date'],
                'duration' => $duration,
            ];
        }

        $this->usedHashes = []; // resets loop for additional costs in getResult function

        return $trades;
    }

    private function getTotalAmount($trades)
    {
        $amount = 0;
        foreach ($trades['trades'] as $key => $trade) {
            $amount += $trade['amount'];
        }
        return $amount;
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
            ->orderBy('id', 'ASC')
            ->get()
            ->toArray();

        $closedByTimestamp = [];

        foreach ($closedBalances as $key => $closedBalance) {
            $balance = 0;
            $nextKey = $key + 1;

            if ($nextKey <= (count($closedBalances) - 1)) { // not exceeding last trade
                if (strpos(strtolower($closedBalances[$key+1]['description']), 'settlement') !== false) {
                    $balance = $closedBalances[$key+1]['balance'];
                } else {
                    $balance = $closedBalance['balance'];    
                }
            } else {
                $balance = $closedBalance['balance'];
            }

            $closedByTimestamp[strtotime($closedBalance['date'])]['balance'] = $balance;
        }

        return $closedByTimestamp;
    }

    private function getResult($trade, $additionalCosts)
    {
        $result = [];
        $sum = 0;
        $sumBuy = 0;
        $sumSell = 0;
        $fees = ['buy' => 0, 'sell' => 0, 'total' => 0, 'funding' => 0, 'settlement' => 0, 'buy_percentage' => 0, 'sell_percentage' => 0, 'total_avg_percentage' => 0];

        $startDate = $trade['trades'][0]['date'];
        $endDate = end($trade['trades'])['date'];

        foreach ($additionalCosts as $key => $cost) {
            if (! array_key_exists($cost['hash'], $this->usedHashes)) {
                if (strtotime($startDate) < strtotime($cost['date'])) {
                    if ((strtotime($endDate) + 10000) > strtotime($cost['date'])) {

                        if (strpos(strtolower($cost['description']), 'funding') !== false) {
                            $fees['funding'] += abs($cost['amount']);
                            $fees['total'] += abs($cost['amount']);
                            $this->usedHashes[$cost['hash']] = $cost['hash'];
                        }

                        if (strpos(strtolower($cost['description']), 'settlement') !== false) {
                            $fees['settlement'] += abs($cost['amount']);
                            $fees['total'] += abs($cost['amount']);
                            $this->usedHashes[$cost['hash']] = $cost['hash'];
                        }
                    }
                }
            }
        }

        foreach ($trade['trades'] as $k => $t) {
            $sum -= ($t['amount'] * $t['price']);
            $fees['total'] -= $t['fee'];

            if ($t['amount'] > 0) {
                $sumBuy += $t['amount'] * $t['price'];
                $fees['buy'] += abs($t['fee']);
            } else {
                $sumSell += $t['amount'] * $t['price'];
                $fees['sell'] += abs($t['fee']);
            }
        }

        $fees['buy_percentage'] = ($fees['buy'] / abs($sumBuy)) * 100;
        $fees['sell_percentage'] = ($fees['sell'] / abs($sumSell)) * 100;
        $fees['total_percentage'] = $fees['buy_percentage'] + $fees['sell_percentage'];
        $fees['total_avg_percentage'] = ($fees['total'] / (abs($sumSell) + abs($sumBuy))) * 100;

        $result['gross_sum'] = $sum;
        $result['net_sum'] = $sum - $fees['total'];
        $result['gross_percentage'] = ((abs($sumSell)- abs($sumBuy)) / abs($sumBuy)) * 100;
        $result['net_percentage'] = (((abs($sumSell) - $fees['total'])- abs($sumBuy)) / abs($sumBuy)) * 100;
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

    private function getCoins()
    {
        $coins = DB::table('trades')->select('coin')->groupBy('coin')->get()->toArray();
        if (isset($_GET['coin'])) {
            $coins = [];
            $obj = new \StdClass();
            $obj->coin = $_GET['coin'];
            $coins[] = $obj;
        }

        return $coins;
    }

    private function getAdditionalCosts($userId)
    {
        $additionalCosts = App\Balance::where('user_id', $userId)
            ->where(function ($query) {
                $query->where('description', 'like', '%funding%')
                ->orWhere('description', 'like', '%settlement%');
            })
            ->get()
            ->toArray();

        return $additionalCosts;
    }

    private function getStats($allTrades, $additionalCosts)
    {
        $wins = 0;
        $losses = 0;
        $winrate = 0;
        $netSum = 0;
        $netPercentage = 0;
        $fees = ['buy' => 0, 'sell' => 0, 'total' => 0, 'funding' => 0, 'settlement' => 0, 'buy_percentage' => 0, 'sell_percentage' => 0, 'total_avg_percentage' => 0];

        foreach ($allTrades as $key => $trade) {
            $params = $trade['parameters'];

            if ($params['winloss'] == 'win') {
                $wins += 1;
            }

            if ($params['winloss'] == 'loss') {
                $losses += 1;
            }

            $result = $this->getResult($trade, $additionalCosts);
            $netSum += $result['net_sum'];
            $netPercentage += $result['net_percentage'];
            $fees['funding'] += $result['fees']['funding'];
            $fees['settlement'] += $result['fees']['settlement'];
            $fees['total'] += ($result['fees']['settlement'] + $result['fees']['funding']);
        }
        $this->usedHashes = []; // resets loop for additional costs in getResult function

        if ($wins && $losses) {
            $winrate = $wins / ($wins + $losses) * 100;
        } else {
            $winrate = 0;
        }

        $stats = [
            'wins' => $wins,
            'losses' => $losses,
            'winrate' => $winrate,
            'net_sum' => $netSum,
            'net_percentage' => $netPercentage,
            'fees' => $fees,
        ];

        return $stats;
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
        $previousUrl = $request->request->get('previous_url');
        $resolved = $request->request->get('resolved'); 

        $trade = App\Trade::where('bitfinex_id', $bitfinex_id)->get();
        $trade = $trade[0];
        $trade->comment = $comment;
        $trade->resolved = $resolved;
        $trade->save();

        return redirect($previousUrl . '#' . $bitfinex_id);
    }
}