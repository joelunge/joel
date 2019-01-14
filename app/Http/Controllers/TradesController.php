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
        // $this->middleware('auth');
    }

    public function hot()
    {
        $coins = config('coins.coins');

        $coinsArr = [];
        foreach ($coins as $coin) {
            $tradesData = DB::connection('mongodb')->table($coin);
            $coinsArr[$coin]['avgChangedPrice'] = $tradesData->avg('changedPrice');
            $coinsArr[$coin]['avgCount'] = $tradesData->avg('count');

            $coinsArr[$coin]['lastMinute'] = DB::connection('mongodb')->table($coin)->orderBy('timestamp', 'DESC')->first();
        }

        return view('hot', ['coins' => $coinsArr]);
    }

    public function hotSingle($coin = false)
    {
        $tradesData = DB::connection('mongodb')->table('trades-t' . $coin . 'usds');

        $avgChangedPrice = $tradesData->avg('changedPrice');
        $avgCount = $tradesData->avg('count');

        $tradesData = $tradesData->orderBy('timestamp', 'DESC')->get();

        $validatedTrades = [];
        foreach ($tradesData as $key => $trade) {
            if ($trade['changedPrice'] > ($avgChangedPrice * 7) || $trade['count'] > ($avgCount * 7)) {
                $validatedTrades[] = $trade;

                $previousCandles = DB::connection('mongodb')->table('trades-t' . $coin . 'usds')->whereBetween('timestamp', [$trade['timestamp'] - (60000*3), $trade['timestamp']])->orderBy('timestamp', 'DESC')->get();

                H::pr($previousCandles);

                H::pr($trade['timestamp']);
            }
        }

        return view('hot_single', ['trades' => $validatedTrades, 'avgChangedPrice' => $avgChangedPrice, 'avgCount' => $avgCount]);
    }

    public function dashboard()
    {
        $hist = \History::trades('XRPUSD', 1540084620000, 1540084679000);

        $trades = explode('],[', str_replace(']]', '', str_replace('[[', '', $hist)));

        foreach ($trades as $key => $trade) {
            $trades[$key] = explode(',', $trade);
        }

        $allTrades = $this->getAllTrades();

        if (isset($_GET['user']) && $_GET['user'] == 'all') {
            $additionalCosts = array_merge($this->getAdditionalCosts(1), $this->getAdditionalCosts(2));
        } else {
            $additionalCosts = $this->getAdditionalCosts(Auth::id());
        }

        $baseline = 0;
        $results = [0];
        foreach ($allTrades as $key => $trade) {
            $result = $this->getResult($trade, $additionalCosts);
            $netSum = $result['net_sum'];
            $baseline = $baseline + $netSum;
            $results[] = $baseline;
        }

        return view('dashboard', ['results' => $results]);
    }

    public function list()
    {
        $allTrades = $this->getAllTrades();

        if (! isset($_GET['show'])) {
            return redirect('/trades?show=30_days');
        }

        $isAllowedToTrade = $this->isAllowedToTrade($allTrades);

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

        $reasons = $this->getReasons();

        if (isset($_GET['user']) && $_GET['user'] == 'all') {
            $additionalCosts = array_merge($this->getAdditionalCosts(1), $this->getAdditionalCosts(2));
        } else {
            $additionalCosts = $this->getAdditionalCosts(Auth::id());
        }

        $stats = $this->getStats($allTrades, $additionalCosts);

        return view('trades.list', ['trades' => $allTrades, 'stats' => $stats, 'isAllowedToTrade' => $isAllowedToTrade, 'indicators' => config('trade.indicators'), 'indicator_names' => config('trade.indicator_names'), 'reasons' => $reasons]);
    }

    private function getAllTrades()
    {
        $userId = Auth::id();
        $this->closedByTimestamp = $this->getClosedBalancesByTimestamp($userId);
        
        $coins = $this->getCoins();

        if (isset($_GET['user']) && $_GET['user'] == 'all') {
            $additionalCosts = array_merge($this->getAdditionalCosts(1), $this->getAdditionalCosts(2));
        } else {
            $additionalCosts = $this->getAdditionalCosts($userId);
        }

        $allTrades = [];
        if (isset($_GET['user']) && $_GET['user'] == 'all') {
            foreach ($coins as $coin) {
                $trades = $this->getTrades($coin->coin, 1);
                $trades = $this->getTradesByTrade($trades);
                $trades = $this->setTradeParameters($trades, $additionalCosts);

                $allTrades[] = $trades;
            }

            foreach ($coins as $coin) {
                $trades = $this->getTrades($coin->coin, 2);
                $trades = $this->getTradesByTrade($trades);
                $trades = $this->setTradeParameters($trades, $additionalCosts);

                $allTrades[] = $trades;
            }
        } else {
            foreach ($coins as $coin) {
                $trades = $this->getTrades($coin->coin, $userId);
                $trades = $this->getTradesByTrade($trades);
                $trades = $this->setTradeParameters($trades, $additionalCosts);

                $allTrades[] = $trades;
            }    
        }        
        $allTrades = array_merge(...$allTrades);
        
        $allTrades = $this->sortByDate($allTrades);

        return $allTrades;
    }

    private function getTrades($coin, $userId)
    {
        if (isset($_GET['user']) && $_GET['user'] != 'all') {
            $userId = $_GET['user'];
        }

        $trades = App\Trade::where('coin', '=', $coin);
        $trades = $trades->where('date', '>', '2018-06-19 00:00:00');
            
        $trades = $trades->where('user_id', $userId)
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
                            (
                                array_key_exists(strtotime($trade['date']), $this->closedByTimestamp)
                                || array_key_exists(strtotime($trade['date'])+1, $this->closedByTimestamp) // sometimes the closed position row is lagging 1 second
                            ) && // this trade is closing
                            (! array_key_exists(strtotime($trades[$nextKey]['date']), $this->closedByTimestamp)) // next trade is not closing
                        ) {
                            $i++;
                        }
                    }
                } else {
                    if (round($amount) == 0) {
                        if (
                            (
                                array_key_exists(strtotime($trade['date']), $this->closedByTimestamp)
                                || array_key_exists(strtotime($trade['date'])+1, $this->closedByTimestamp) // sometimes the closed position row is lagging 1 second
                            ) && // this trade is closing
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
            $duration['hours'] = gmdate('H', $duration['timestamp']);
            $duration['minutes'] = gmdate('i', $duration['timestamp']);
            $duration['seconds'] = gmdate('s', $duration['timestamp']);

            $amount = $this->getTotals($trade)['amount'];
            $volume = $this->getTotals($trade)['volume'];

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
                'volume' => $volume,
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

    private function getTotals($trades)
    {
        $totals = [
            'amount' => 0,
            'volume' => 0,
        ];

        foreach ($trades['trades'] as $key => $trade) {
            $totals['amount'] += (abs($trade['amount']) / 2);
            $totals['volume'] += (abs($trade['amount']) * $trade['price']);
        }

        return $totals;
    }

    private function isLongOrShort($firstOrderAmount)
    {
        return ($firstOrderAmount > 0) ? 'Long' : 'Short';
    }

    private function getClosingBalance($trade)
    {
        if (isset($this->closedByTimestamp[strtotime($trade['trades'][(count($trade['trades']) - 1)]['date'])]['balance'])) {
            return $this->closedByTimestamp[strtotime($trade['trades'][(count($trade['trades']) - 1)]['date'])]['balance'];
        } elseif ((isset($trade['trades'][(count($trade['trades']) - 2)])) && (isset($trade['trades'][(count($trade['trades']) - 2)]['date']['balance']))) {
            return $this->closedByTimestamp[strtotime($trade['trades'][(count($trade['trades']) - 2)]['date'])]['balance'];
        } else {
            return 0;
        }
    }

    private function getClosedBalancesByTimestamp($userId)
    {
        if (isset($_GET['user']) && $_GET['user'] != 'all') {
            $userId = $_GET['user'];
        }

        $closedBalances = App\Balance::where('description', 'like', '%closed%')
            ->orWhere('description', 'like', '%settlement%');

        if ((! isset($_GET['user'])) || (isset($_GET['user']) && $_GET['user'] != 'all')) {
            $closedBalances = $closedBalances->where('user_id', $userId);
        }
            
        $closedBalances = $closedBalances->orderBy('id', 'ASC')
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
        $result = ['net_percentage' => 0, 'net_sum' => 0];
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

        if (abs($sumBuy)) {
            $fees['buy_percentage'] = ($fees['buy'] / abs($sumBuy)) * 100;
        }

        if (abs($sumSell)) {
            $fees['sell_percentage'] = ($fees['sell'] / abs($sumSell)) * 100;
        }

        $fees['total_percentage'] = $fees['buy_percentage'] + $fees['sell_percentage'];
        $fees['total_avg_percentage'] = ($fees['total'] / (abs($sumSell) + abs($sumBuy))) * 100;

        if (abs($sumBuy) && abs($sumSell)) {
            $result['gross_sum'] = $sum;
            $result['net_sum'] = $sum - $fees['total'];
            $result['gross_percentage'] = ((abs($sumSell) - abs($sumBuy)) / abs($sumBuy)) * 100;
            $result['net_percentage'] = (((abs($sumSell) - $fees['total'])- abs($sumBuy)) / abs($sumBuy)) * 100;    
        }
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
        $baseline = 0;
        $wins = 0;
        $losses = 0;
        $winrate = 0;
        $netSum = 0;
        $netPercentage = 0;
        $gainUsdWins = 0;
        $gainUsdLosses = 0;
        $gainPercentageWins = 0;
        $gainPercentageLosses = 0;
        $volume = 0;
        $positionSize = 0;
        $duration = 0;
        $durationWins = 0;
        $durationLosses = 0;
        $fees = ['buy' => 0, 'sell' => 0, 'total' => 0, 'funding' => 0, 'settlement' => 0, 'buy_percentage' => 0, 'sell_percentage' => 0, 'total_avg_percentage' => 0];

        foreach ($allTrades as $key => $trade) {
            $params = $trade['parameters'];

            if ($params['winloss'] == 'win') {
                $wins += 1;
                $gainUsdWins += $params['result']['net_sum'];
                $gainPercentageWins += $params['result']['net_percentage'];
                $durationWins += $params['duration']['timestamp'];
            }

            if ($params['winloss'] == 'loss') {
                $losses += 1;
                $gainUsdLosses += $params['result']['net_sum'];
                $gainPercentageLosses += $params['result']['net_percentage'];
                $durationLosses += $params['duration']['timestamp'];
            }

            $duration += $params['duration']['timestamp'];

            $result = $this->getResult($trade, $additionalCosts);
            $netSum = $netSum + $result['net_sum'];
            $netPercentage += $result['net_percentage'];
            $volume += $params['volume'];
            $positionSize = ($volume / count($allTrades)) / 2;
            $fees['funding'] += $result['fees']['funding'];
            $fees['settlement'] += $result['fees']['settlement'];
            $fees['total'] += ($result['fees']['settlement'] + $result['fees']['funding']);
        }
        $this->usedHashes = []; // resets loop for additional costs in getResult function

        if ($wins && $losses) {
            $winrate = $wins / ($wins + $losses) * 100;
        } elseif ($wins && ! $losses) {
            $winrate = 100;
        } elseif (! $wins && $losses) {
            $winrate = 0;
        }

        $stats = [
            'wins' => $wins,
            'losses' => $losses,
            'winrate' => $winrate,
            'net_sum' => $netSum,
            'net_percentage' => $netPercentage,
            'avg_usd_wins' => $gainUsdWins / $wins,
            'avg_usd_losses' => ($losses) ? $gainUsdLosses / $losses : 0,
            'avg_percentage_wins' => $gainPercentageWins / $wins,
            'avg_percentage_losses' => ($losses) ? $gainPercentageLosses / $losses : 0,
            'volume' => $volume,
            'position_size' => $positionSize,
            'duration' => $duration / count($allTrades),
            'duration_wins' => $durationWins / $wins,
            'duration_losses' => ($losses) ? $durationLosses / $losses : 0,
            'fees' => $fees,
        ];

        return $stats;
    }

    private function getReasons()
    {
        $reasons = App\Reason::all()->toArray();

        foreach ($reasons as $key => $reason) {
            $reasons[$key]['count'] = DB::table('reasons_trades')->where('reason_id', $reason['id'])->count();
        }

        usort($reasons, function($a, $b) {
            return $b['count'] <=> $a['count'];
        });

        return $reasons;
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

        $reasons = $this->getReasons();

        return view('trades.edit', ['trade' => $trade, 'indicators' => config('trade.indicators'), 'indicator_names' => config('trade.indicator_names'), 'reasons' => $reasons, 'bitfinex_id' => $bitfinex_id]);
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

        foreach (config('trade.indicators') as $key => $value) {
            if ($request->request->get($key) == 'null' || $request->request->get($key) == null) {
                $trade->{$key} = null;
            } else {
                $trade->{$key} = $request->request->get($key);
            }
        }

        foreach (App\Reason::all() as $reason) {
            if ($request->request->get('reason_'.$reason->id) == 0 || $request->request->get('reason_' . $reason->id) == 'null' || $request->request->get('reason_'.$reason->id) == null) {
                DB::table('reasons_trades')->where('reason_id', '=', $reason->id)->where('bitfinex_id', '=', $bitfinex_id)->delete();
            } else {
                if (! DB::table('reasons_trades')->where('reason_id', $reason->id)->where('bitfinex_id', $bitfinex_id)->count()) {
                    DB::table('reasons_trades')->insert(['reason_id' => $reason->id, 'bitfinex_id' => $bitfinex_id]);
                }
            }
        }

        if (is_array($request->request->get('new_reason_fail'))) {
            foreach ($request->request->get('new_reason_fail') as $newReason) {
                if (! count(App\Reason::where('reason', $newReason)->get()) && $newReason != '') {
                    $reason = new App\Reason;
                    $reason->reason = $newReason;
                    $reason->type = 'fail';
                    $reason->added_by = Auth::id();
                    $reason->save();

                    DB::table('reasons_trades')->insert(['reason_id' => $reason->id, 'bitfinex_id' => $bitfinex_id]);
                }
            }
        }

        if ($request->request->get('new_reason_success')) {
            foreach ($request->request->get('new_reason_success') as $newReason) {
                if (! count(App\Reason::where('reason', $newReason)->get()) && $newReason != '') {
                    $reason = new App\Reason;
                    $reason->reason = $newReason;
                    $reason->type = 'success';
                    $reason->added_by = Auth::id();
                    $reason->save();

                    DB::table('reasons_trades')->insert(['reason_id' => $reason->id, 'bitfinex_id' => $bitfinex_id]);
                }
            }
        }

        foreach (App\Reason::all() as $reason) {
            if (! DB::table('reasons_trades')->where('reason_id', $reason->id)->count()) {
                $reason->delete();
            }
        }

        $trade->resolved = $resolved;
        $trade->save();

        return redirect($previousUrl . '#' . $bitfinex_id);
    }

    private function isAllowedToTrade($allTrades)
    {
        $isAllowedToTrade = true;
        foreach ($allTrades as $key => $trade) {
            if (! $trade['trades'][0]['resolved'] && $trade['parameters']['result']['net_sum'] < 0) {
                $isAllowedToTrade = false;
            }
        }

        return $isAllowedToTrade;
    }

    public function scrape()
    {
        for ($i=0; $i < 30; $i++) {
            sleep(4);
            // $start = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2013-03-31 00:00:00');
            // $end = $start->modify('+10 hours');

            // $request = sprintf('https://api.bitfinex.com/v2/trades/tBTCUSD/hist?start=%s&end=%s&limit=1000&sort=1', $start->getTimestamp() * 1000, $end->getTimestamp() * 1000);
            // H::pr($request);

            $lastTrade = App\Btctrade::orderBy('id', 'desc')->first();

            $start = $lastTrade->timestamp;
            $end = $start + (86400000 * 4);

            $request = sprintf('https://api.bitfinex.com/v2/trades/tBTCUSD/hist?start=%s&end=%s&limit=1000&sort=1', $start, $end);
    // // H::pr($request);
            $trades = file_get_contents($request);
            $trades = json_decode($trades);
            // H::pr($request);

            foreach ($trades as $trade) {
                // echo sprintf('%s ___ %s ___ %s ___ %s', $trade[0], date('H:i:s', $trade[1]/1000), $trade[2], $trade[3]);
                // echo sprintf('%s,    %s,       %s,            %s', $trade[0], $trade[1], $trade[2], $trade[3]);
                // echo "<br>";
                // echo "<br>";
                // $exist = App\Btctrade::where('bfx_id', $trade[0])->first();

                if (date('Y', $trade[1] / 1000) == 2019) {
                    echo "wrong start and end timestamps"; exit;
                }

                DB::statement(sprintf('insert ignore into btctrades (bfx_id, timestamp, date, amount, price, updated_at, created_at) values (%s, %s, "%s", %s, %s, "%s", "%s")', $trade[0], $trade[1], date('Y-m-d H:i:s', $trade[1] / 1000), $trade[2], $trade[3], date('Y-m-d H:i:s', time()), date('Y-m-d H:i:s', time())));

                // if ($exist == null) {
                //     $btctrade = new App\Btctrade;
                //     $btctrade->bfx_id = $trade[0];
                //     $btctrade->timestamp = $trade[1];
                //     $btctrade->date = date('Y-m-d H:i:s', $trade[1] / 1000);
                //     $btctrade->amount = $trade[2];
                //     $btctrade->price = $trade[3];
                //     $btctrade->save();
                // }
            }
        }
    }

    public function scrapeCandles()
    {
        // $start = 1542301200363;
        // $end = $start + 86400000;

        $lastCandle = App\Bfxcandle::orderBy('id', 'desc')->first();

        $start = $lastCandle->timestamp;
        $end = $start + 86400000;

        $request = sprintf('https://api.bitfinex.com/v2/candles/trade:1m:tBTCUSD/hist?sort=1&limit=1000&start=%s&end=%s', $start, $end);

        $candles = file_get_contents($request);
        $candles = json_decode($candles);

        foreach ($candles as $candle) {
            $exist = App\Bfxcandle::where('timestamp', $candle[0])->first();
            if ($exist == null) {
                $bfxcandle = new App\Bfxcandle;
                $bfxcandle->timestamp = $candle[0];
                $bfxcandle->open = $candle[1];
                $bfxcandle->close = $candle[2];
                $bfxcandle->high = $candle[3];
                $bfxcandle->low = $candle[4];
                $bfxcandle->volume = $candle[5];
                $bfxcandle->save();
            }
        }
        exit;
    }

    public function showBfxTrades()
    {
        $values = [
            4183.2,
            3977.3,
            3957.8,
            3950.4,
            3949.8,
            3946.8,
            3947.7,
            3953.5,
            3954.9,
            3954.6,
            3934.8,
            3928.5,
            3926.8,
            3920.1,
            3925.2,

        ];

        H::pr(\Indicators::rsi($values));

        H::pr($t);
        exit;

        // $allTrades = \App\Bfxtrade::where('amount', '>', 3)->orWhere('amount', '<', -3)->get();
        if (isset($_GET['pagination'])) {
            $pagination = $_GET['pagination'];
        }

        $startDate = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2018-11-19 00:00:00');

        if (isset($pagination)) {
            $startDate = $startDate->modify('+'.$pagination.' days');
        }

        $endDate = $startDate->modify('+5 hours');

        // $allTrades = \App\Bfxtrade::where(function ($query) {
        //     $query->where('amount', '>', 250);
        // })->orWhere(function($query) {
        //     $query->where('amount', '<', -250);
        // })->get();

        $allTrades = \App\Bfxtrade::whereBetween('timestamp', [$startDate->getTimestamp() * 1000, $endDate->getTimestamp() * 1000])->get();

        return view('bfxtrades', ['trades' => $allTrades]);
    }

    public function makeIndicators()
    {
    for( $i = 0; $i<1000; $i++ ) {

        $candle = App\Bfxcandle::where('checked', null)->first();

        $start = $candle->timestamp;
        $end = $start + 59999;

        $allTrades = \App\Bfxtrade::whereBetween('timestamp', [$start, $end])->get();
        $buyTrades = \App\Bfxtrade::whereBetween('timestamp', [$start, $end])->where('amount', '>', 0)->get();
        $sellTrades = \App\Bfxtrade::whereBetween('timestamp', [$start, $end])->where('amount', '<', 0)->get();

        $ind = [
            'open' => $allTrades[0]->price,
            'high' => $allTrades[0]->price,
            'low' => $allTrades[0]->price,
            'close' => $allTrades[$allTrades->count() -1]->price,
            'tradeCount' => $allTrades->count(),
            'buyCount' => 0,
            'sellCount' => 0,
            'volume' => 0,
            'buyVolume' => 0,
            'sellVolume' => 0,
            'avgAmount' => 0,
            'avgBuyAmount' => 0,
            'avgSellAmount' => 0,
            'standardDev' => 0,
            'buyStandardDev' => 0,
            'sellStandardDev' => 0,
            'changedPrice' => 0,
            'changedPriceUp' => 0,
            'changedPriceDown' => 0,
            'buyChangedPrice' => 0,
            'buyChangedPriceUp' => 0,
            'buyChangedPriceDown' => 0,
            'sellChangedPrice' => 0,
            'sellChangedPriceUp' => 0,
            'sellChangedPriceDown' => 0,
            'checked' => 1,
        ];

        $allTradeAmounts = [];
        $buyTradeAmounts = [];
        $sellTradeAmounts = [];

        $prevPrice = $allTrades[0]->price;

        foreach ($allTrades as $trade) {
            if ($trade->price > $ind['high']) {
                $ind['high'] = $trade->price;
            }

            if ($trade->price < $ind['low']) {
                $ind['low'] = $trade->price;
            }

            $ind['volume'] = $ind['volume'] + abs($trade->amount);

            $allTradeAmounts[] = abs($trade->amount);

            if ($trade->price <> $prevPrice) {
                $ind['changedPrice'] = $ind['changedPrice'] + 1;

                if ($trade->amount > 0) {
                    $ind['buyChangedPrice'] = $ind['buyChangedPrice'] + 1;
                }

                if ($trade->amount <0) {
                    $ind['sellChangedPrice'] = $ind['sellChangedPrice'] + 1;
                }
            }

            if ($trade->price > $prevPrice) {
                $ind['changedPriceUp'] = $ind['changedPriceUp'] + 1;

                if ($trade->amount > 0) {
                    $ind['buyChangedPriceUp'] = $ind['buyChangedPriceUp'] + 1;
                }

                if ($trade->amount <0) {
                    $ind['sellChangedPriceUp'] = $ind['sellChangedPriceUp'] + 1;
                }
            }

            if ($trade->price < $prevPrice) {
                $ind['changedPriceDown'] = $ind['changedPriceDown'] + 1;

                if ($trade->amount > 0) {
                    $ind['buyChangedPriceDown'] = $ind['buyChangedPriceDown'] + 1;
                }

                if ($trade->amount <0) {
                    $ind['sellChangedPriceDown'] = $ind['sellChangedPriceDown'] + 1;
                }
            }

            if ($trade->amount > 0) {
                $ind['buyCount'] = $ind['buyCount'] + 1;
                $ind['buyVolume'] = $ind['buyVolume'] + abs($trade->amount);
                $buyTradeAmounts[] = abs($trade->amount);
            }

            if ($trade->amount < 0) {
                $ind['sellCount'] = $ind['sellCount'] + 1;
                $ind['sellVolume'] = $ind['sellVolume'] + abs($trade->amount);
                $sellTradeAmounts[] = abs($trade->amount);
            }

            $prevPrice = $trade->price;
        }

        $ind['avgAmount'] = $ind['volume'] / $allTrades->count();
        if ($buyTrades->count()) {
            $ind['avgBuyAmount'] = $ind['buyVolume'] / $buyTrades->count();
        } else {
            $ind['avgBuyAmount'] = 0;
        }

        if ($sellTrades->count()) {
            $ind['avgSellAmount'] = $ind['sellVolume'] / $sellTrades->count();
        } else {
            $ind['avgSellAmount'] = 0;
        }

        $ind['standardDev'] = $this->standardDeviation($allTradeAmounts);

        if ($buyTradeAmounts) {
            $ind['buyStandardDev'] = $this->standardDeviation($buyTradeAmounts);    
        }
        
        if ($sellTradeAmounts) {
            $ind['sellStandardDev'] = $this->standardDeviation($sellTradeAmounts);
        }

        $candle->fill($ind);
        $candle->save();
        // exit;
        // H::pr($ind);
        }
        exit;
    }

    private function standardDeviation($arr) 
    { 
        $num_of_elements = count($arr); 
          
        $variance = 0.0; 
          
                // calculating mean using array_sum() method 
        $average = array_sum($arr)/$num_of_elements; 
          
        foreach($arr as $i) 
        { 
            // sum of squares of differences between  
                        // all numbers and means. 
            $variance += pow(($i - $average), 2); 
        } 
          
        return (float)sqrt($variance/$num_of_elements); 
    }
}