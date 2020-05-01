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

    public function new($coin, $amount, $price, $direction, $type)
    {
        $bfx = new \App\Bitfinex(env('BFX_K'), env('BFX_SC'), 'v1');
        $bfx->cancel_all_orders();
        $bfx->new_order($coin, strval(abs($amount)), strval($price), 'bitfinex', $direction, $type);

        return redirect('/positions');
    }

    public function test()
    {
        exit;
        $bfx = new \App\Bitfinex(env('BFX_K'), env('BFX_SC'), 'v1');
        $positions = $bfx->get_positions();
        foreach (range(1, 2) as $i) {
            if (! empty($positions)) {
                echo "wtf";
            }
        }
        exit;
        // $bfx = new \App\Bitfinex(env('BFX_K'), env('BFX_SC'), 'v1');
        // \H::pr($bfx->get_symbols());

        exit;
        // echo "return_var is: $return_var" . "\n";
        // var_dump($output);

        // $url = 'https://api.bitfinex.com/v2/auth/r/positions';

        // $data = array(
        //   'apiKey' => '',
        //   'apiSecret' => '',
        //   'price' => 9342,
        //   'symbol' => 'tBTCUSD',
        //   'intent' => 'SELL'
        // );

        // $options = array(
        //   'http' => array(
        //     'method'  => 'POST',
        //     'content' => json_encode($data),
        //     'header'=>  "Content-Type: application/json\r\n" . "Accept: application/json\r\n"
        //     )
        // );

        // $context  = stream_context_create($options);
        // $result = file_get_contents($url, false, $context);
        // $response = json_decode($result);

        // echo "<pre>";
        // print_r($response);
        // exit;
    }

    public function test2()
    {
        exit;
        $url = 'http://13.48.209.13:3002/api/order/new';

        $data = array(
          'apiKey' => env('BFX_K'),
          'apiSecret' => env('BFX_SC'),
          'price' => 9342,
          'symbol' => 'tBTCUSD',
          'intent' => 'SELL'
        );

        $options = array(
          'http' => array(
            'method'  => 'POST',
            'content' => json_encode($data),
            'header'=>  "Content-Type: application/json\r\n" . "Accept: application/json\r\n"
            )
        );

        $context  = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        $response = json_decode($result);

        echo "<pre>";
        print_r($response);
        exit;
    }

    // public function test()
    // {
    //     $curl = curl_init('https://api.apify.com/v2/browser-info');
    //     curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    //     curl_setopt($curl, CURLOPT_PROXY, 'http://proxy.apify.com:8000');
    //     // Replace <YOUR_PROXY_PASSWORD> below with your password
    //     // found at https://my.apify.com/proxy
    //     curl_setopt($curl, CURLOPT_PROXYUSERPWD, 'auto:E8eGjqtyRkREDFzBfXEdKMi83');
    //     $response = curl_exec($curl);
    //     curl_close($curl);
    //     if ($response) {
    //         echo "hej";
    //         echo $response;
    //     } else {
    //         echo "inte hej";
    //     }
    // }

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
            'avg_usd_wins' => ($wins) ? $gainUsdWins / $wins : 0,
            'avg_usd_losses' => ($losses) ? $gainUsdLosses / $losses : 0,
            'avg_percentage_wins' => ($wins) ? $gainPercentageWins / $wins: 0,
            'avg_percentage_losses' => ($losses) ? $gainPercentageLosses / $losses : 0,
            'volume' => $volume,
            'position_size' => $positionSize,
            'duration' => (count($allTrades)) ? $duration / count($allTrades) : 0,
            'duration_wins' => ($wins) ? $durationWins / $wins : 0,
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
        $coins = [
            'BTC' => 1364688000000, // KLAR
            'ETH' => 1466812800000, // KLAR
            'EOS' => 1498867200000, // KLAR
            'BAB' => 1542067200000, // KLAR
            'LTC' => 1383091200000, // KLAR
            'XRP' => 1495152000000, // KLAR
            'OMG' => 1499990400000, // KLAR
            'IOT' => 1497225600000, // KLAR
            'NEO' => 1504742400000, // KLAR
            'XMR' => 1480464000000, // KLAR
            'ETC' => 1470009600000, // KLAR
            // 'BCH' => 1513728000000, // KLAR - BEHÖVER INTE UPPDATERAS
        ];

        foreach ($coins as $coin => $startTimestamp) {
            for ($i=0; $i < 30; $i++) {
                sleep(2);
                $lastTrade = App\Btctrade::from(sprintf('upw_%s_trades', strtolower($coin)))->orderBy('id', 'desc')->first();
                // $start = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $lastTrade->timestamp);
                // $end = $start->modify('+24 hours');

                if (isset($lastTrade->timestamp)) {
                    $start = $lastTrade->timestamp;
                } else {
                    $start = $startTimestamp;
                }

                if (date('Y-m-d', $start / 1000) == date('Y-m-d', time())) {
                    break;
                }

                if ($coin == 'ETH' && date('Y-m-d', $start / 1000) == '2016-08-02') {
                    $start = 1470865301000;
                }

                if ($coin == 'ETC' && date('Y-m-d', $start / 1000) == '2016-08-02') {
                    $start = 1470865301000;
                }

                if ($coin == 'LTC' && date('Y-m-d', $start / 1000) == '2016-08-02') {
                    $start = 1470865301000;
                }

                $end = $start + (86400000 * 1);

                $request = sprintf('https://api.bitfinex.com/v2/trades/t%sUSD/hist?start=%s&end=%s&limit=5000&sort=1', $coin, $start, $end);

                // $lastTrade = App\Btctrade::orderBy('id', 'desc')->first();

                // $start = $lastTrade->timestamp;
                // $end = $start + (86400000 * 1);

                // $opts = array('http'=>array('header' => "User-Agent:MyAgent/1.0\r\n"));
                // $context = stream_context_create($opts);
                // $request = file_get_contents(sprintf('https://api.bitfinex.com/v2/trades/t%sUSD/hist?start=%s&end=1547510400000&limit=5000&sort=1', $coin, $start, $end) ,false,$context);
                // H::pr($request);

                $trades = file_get_contents($request);
                $trades = json_decode($trades);
                // H::pr($request);

                foreach ($trades as $trade) {
                    // echo sprintf('%s ___ %s ___ %s ___ %s', $trade[0], date('H:i:s', $trade[1]/1000), $trade[2], $trade[3]);
                    // echo sprintf('%s,    %s,       %s,            %s', $trade[0], $trade[1], $trade[2], $trade[3]);
                    // echo "<br>";
                    // echo "<br>";
                    // $exist = App\Btctrade::where('bfx_id', $trade[0])->first();

                    $year = date('Y', $trade[1] / 1000);
                    $month = date('m', $trade[1] / 1000);
                    $day = date('d', $trade[1] / 1000);

                    $currentYear = date('Y', time());
                    $currentMonth = date('m', time());
                    $currentDay = date('d', time());

                    if ($year == $currentYear && $month == $currentMonth && $day == $currentDay) {
                        echo $coin . " wrong start and end timestamps<br />"; break 2;
                    } else {
                        DB::statement(sprintf('insert ignore into upw_%s_trades (bfx_id, timestamp, amount, price, updated_at, created_at) values (%s, %s, %s, %s, "%s", "%s")', strtolower($coin), $trade[0], $trade[1], $trade[2], $trade[3], date('Y-m-d H:i:s', time()), date('Y-m-d H:i:s', time())));
                    }
                }
            }
        }
    }

    public function scrapeCandles()
    {
        $coin = 'XRP';
        $timeframe = '1m';
        $loop = 30;

        for ($i=0; $i < $loop; $i++) {
            sleep(4);
            // $start = 1495216644000;
            // $end = $start + 86400000; // 24 hours

            $lastCandle = App\Btccandle::orderBy('id', 'desc')->first();

            $start = $lastCandle->timestamp;
            $end = $start + (86400000 * 1);

            $request = sprintf('https://api.bitfinex.com/v2/candles/trade:%s:t%sUSD/hist?sort=1&limit=5000&start=%s', $timeframe, $coin, $start, $end);

            $candles = file_get_contents($request);
            $candles = json_decode($candles);

            foreach ($candles as $candle) {
                $year = date('Y', $candle[0] / 1000);
                $month = date('m', $candle[0] / 1000);
                $day = date('d', $candle[0] / 1000);

                if ($year == 2019 && $month == 02 && $day == 02) {
                    echo "wrong start and end timestamps"; exit;
                }

                DB::statement(sprintf('insert ignore into %s_%s_candles (timestamp, open, close, high, low, volume, updated_at, created_at) values (%s, %s, %s, %s, %s, %s, "%s", "%s")', $coin, $timeframe, $candle[0], $candle[1], $candle[2], $candle[3], $candle[4], $candle[5], date('Y-m-d H:i:s', time()), date('Y-m-d H:i:s', time())));
                // $exist = App\Btccandle::where('timestamp', $candle[0])->first();
                // if ($exist == null) {
                //     $bfxcandle = new App\Btccandle;
                //     $bfxcandle->timestamp = $candle[0];
                //     $bfxcandle->open = $candle[1];
                //     $bfxcandle->close = $candle[2];
                //     $bfxcandle->high = $candle[3];
                //     $bfxcandle->low = $candle[4];
                //     $bfxcandle->volume = $candle[5];
                //     $bfxcandle->save();
                // }
            }
        }
    }

    public function showBfxTrades()
    {
        // $allTrades = \App\Bfxtrade::where('amount', '>', 3)->orWhere('amount', '<', -3)->get();
        if (isset($_GET['pagination'])) {
            $pagination = $_GET['pagination'];
        }

        $startDate = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2017-12-12 00:00:00');

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

    public function showBfxCandles()
    {
        if (isset($_GET['pagination'])) {
            $pagination = $_GET['pagination'];
        }

        $startDate = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2018-04-11 00:00:00');

        if (isset($pagination)) {
            $startDate = $startDate->modify('+'.$pagination.' days');
        }

        $endDate = $startDate->modify('+72 hours');

        // $allTrades = \App\Bfxtrade::where(function ($query) {
        //     $query->where('amount', '>', 250);
        // })->orWhere(function($query) {
        //     $query->where('amount', '<', -250);
        // })->get();

        $allCandles = \App\Btccandle::whereBetween('date', [$startDate, $endDate])->get();

        return view('bfxcandles', ['candles' => $allCandles]);
    }

    public function updateRsi()
    {
        // exit;
        ini_set('memory_limit', '12000M');
        ini_set('max_execution_time', 86400);

        // $candles = \App\Btccandle::select('id', 'close', 'rsi', 'timestamp', 'checked')->get();
        $candles = \App\Btccandle::get();

        // foreach ($candles as $key => $candle) {
        //     $candle->open = $candle->open * 6000;
        //     $candle->close = $candle->close * 6000;
        //     $candle->high = $candle->high * 6000;
        //     $candle->low = $candle->low * 6000;
        //     $candle->save();
        // }
        // exit;

        // foreach ($candles as $key => $c) {
            // $c->timestamp = strtotime($c->date) * 1000;

            // if ($c->id > 14) {
                // DB::statement(sprintf('update btc_full_dataset set rsi_5m = %s where timestamp between %s and %s' , $c->rsi, $c->timestamp, $c->timestamp + 299999)); // 5 min rsi
                // DB::statement(sprintf('update btc_full_dataset set rsi_15m = %s where timestamp between %s and %s' , $c->rsi, $c->timestamp, $c->timestamp + 899999)); // 15 min rsi
                // DB::statement(sprintf('update btc_full_dataset set rsi_30m = %s where timestamp between %s and %s' , $c->rsi, $c->timestamp, $c->timestamp + 1799999)); // 30 min rsi
                // DB::statement(sprintf('update btc_full_dataset set rsi_1h = %s where timestamp between %s and %s' , $c->rsi, $c->timestamp, $c->timestamp + 3599999)); // 1 hour rsi
            // }
        // }
        // exit;

        // foreach ($candles as $key => $c) {
        //     $c->date = date('Y-m-d H:i:s', $c->timestamp / 1000);
        //     $c->save();
        // }
        // exit;

        $prices = [];
        $ids = [];
        foreach ($candles as $key => $c) {
            $prices[] = $c->close;
            $ids[] = $c->id;
        }

        $rsis = \Indicators::Rsi($ids, $prices);

        foreach ($candles as $key => $c) {
            if ($c->id > 14) {
                $c->rsi_1m = $rsis[$c->id];
                $c->save();
            }
        }
        exit;

        // $candles2 = \App\Btccandle::select('id', 'close')->offset(500000)->limit(500000)->get();
        // $candles3 = \App\Btccandle::select('id', 'close')->offset(1000000)->limit(500000)->get();
        // $candles4 = \App\Btccandle::select('id', 'close')->offset(1500000)->limit(500000)->get();
        // $candles5 = \App\Btccandle::select('id', 'close')->offset(2000000)->limit(500000)->get();

        // $candles = \App\Btccandle::select('id', 'close', 'rsi')->limit(100)->get();
        // $candles2 = \App\Btccandle::select('id', 'close', 'rsi')->offset(100)->limit(100)->get();
        // $candles3 = \App\Btccandle::select('id', 'close', 'rsi')->offset(200)->limit(100)->get();
        // $candles4 = \App\Btccandle::select('id', 'close', 'rsi')->offset(300)->limit(100)->get();
        // $candles5 = \App\Btccandle::select('id', 'close', 'rsi')->offset(400)->limit(100)->get();
        
        // $allCandles = array_merge($candles->toArray(), $candles2->toArray(), $candles3->toArray(), $candles4->toArray(), $candles5->toArray());

        $allCandles = $candles->toArray();

        $candlesWithRsi = [];
        $prices = [];
        foreach ($allCandles as $key => $c) {
            $prices[$c['id']] = $c['close'];
        }

        foreach ($allCandles as $key => $c) {
            if ($c['id'] > 15) {
                $tmpPrices = $prices;
                $keys = array_flip(array_keys($tmpPrices));
                $rsiData = array_splice($tmpPrices, 0, $keys[$c['id']]);
                $candlesWithRsi[$key] = \Indicators::Rsi($rsiData);
            }
        }

        foreach ($candles as $key => $cc) {
            if ($cc->id > 15) {
                if ($cc->rsi == NULL) {
                    $cc->rsi = $candlesWithRsi[$cc->id];
                    $cc->save();
                }
            }
        }

        exit;

        // $keys = array_flip(array_keys($prices));
        // echo $keys[2];

        // H::pr($keys);

        // H::pr($prices);
        // H::pr(array_splice($prices, 0, $keys[2]));

        H::pr($prices);
        H::pr(\Indicators::Rsi($prices));

        H::pr(count($allCandles));
        exit;

        $arr = [];
        for( $i = 0; $i<2000000; $i++ ) {
            $arr[$i] = $i;
        }
        H::pr($arr);
        exit;

        for( $i = 0; $i<10000; $i++ ) {
            $currentCandle = App\Btccandle::where('rsi', null)->where('id', '>', 14)->first();

            $candles = \App\Btccandle::select('close')->where('id', '<=', $currentCandle->id)->orderBy('id', 'DESC')->limit(20000)->get()->toArray();

            $candles = array_reverse($candles);

            $prices = [];
            foreach ($candles as $key => $candle) {
                $prices[$key] = $candle['close'];
            }

            $currentCandle->rsi = \Indicators::Rsi($prices);
            $currentCandle->save();
        }
    }

    public function makeIndicators()
    {
        ini_set('memory_limit', '12000M');
        ini_set('max_execution_time', 86400);

        $allCandles = App\Btccandle::where('checked', null)->get();

        foreach ($allCandles as $key => $candle) {
            $start = $candle->timestamp;
            // $end = $start + 86399999; // 24 hours - 1 millisecond
            $end = $start + 59999; // 60 seconds - 1 millisecond

            $allTrades = \App\Btctrade::whereBetween('timestamp', [$start, $end])->get();
            // $buyTrades = \App\Btctrade::whereBetween('timestamp', [$start, $end])->where('amount', '>', 0)->get();
            // $sellTrades = \App\Btctrade::whereBetween('timestamp', [$start, $end])->where('amount', '<', 0)->get();

            if ($allTrades->isEmpty()) {
                $ind = [
                    'open' => 0,
                    'high' => 0,
                    'low' => 0,
                    'close' => 0,
                    'tradeCount' => 0,
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

                $candle->fill($ind);
                $candle->save();

                continue;
            } else {
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
            }

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

                $ind['volume'] = $ind['volume'] + abs($trade->amount * $trade->price);

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
                    $ind['buyVolume'] = $ind['buyVolume'] + abs($trade->amount * $trade->price);
                    $buyTradeAmounts[] = abs($trade->amount);
                }

                if ($trade->amount < 0) {
                    $ind['sellCount'] = $ind['sellCount'] + 1;
                    $ind['sellVolume'] = $ind['sellVolume'] + abs($trade->amount * $trade->price);
                    $sellTradeAmounts[] = abs($trade->amount);
                }

                $prevPrice = $trade->price;
            }

            $ind['avgAmount'] = $ind['volume'] / $allTrades->count();
            // if ($buyTrades->count()) {
            //     $ind['avgBuyAmount'] = $ind['buyVolume'] / $buyTrades->count();
            // } else {
            //     $ind['avgBuyAmount'] = 0;
            // }

            // if ($sellTrades->count()) {
            //     $ind['avgSellAmount'] = $ind['sellVolume'] / $sellTrades->count();
            // } else {
            //     $ind['avgSellAmount'] = 0;
            // }

            $ind['standardDev'] = $this->standardDeviation($allTradeAmounts);

            if ($buyTradeAmounts) {
                $ind['buyStandardDev'] = $this->standardDeviation($buyTradeAmounts);    
            }
            
            if ($sellTradeAmounts) {
                $ind['sellStandardDev'] = $this->standardDeviation($sellTradeAmounts);
            }

            $candle->fill($ind);
            $candle->save();
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

    public function normalizeVolume()
    {
        ini_set('memory_limit', '12000M');
        ini_set('max_execution_time', 86400);

        // Get all 1-minute candles
        $candles = App\Btccandle::get();
        
        // Can't divide by 0, so I set the default value to 1 for each feature
        $maxVolume = 1;
        $maxBuyVolume = 1;
        $maxSellVolume = 1;
        $maxTradeCount = 1;
        $maxBuyCount = 1;
        $maxSellCount = 1;
        $maxChangedPrice = 1;

        // Loop through all 1-minute candles
        foreach ($candles as $key => $c) {

            // if the current candles volume bigger than the maxVolume, save this as a new max
            // also, do volume * open price to get the volume in USD instead of amount of coins
            if (($c->volume * $c->open) > $maxVolume) {
                $maxVolume = $c->volume * $c->open;
            }

            // Same as above, but for buyVolume
            if (($c->buyVolume * $c->open) > $maxBuyVolume) {
                $maxBuyVolume = $c->buyVolume * $c->open;
            }

            // Same as above, but for sellVolume
            if (($c->sellVolume * $c->open) > $maxSellVolume) {
                $maxSellVolume = $c->sellVolume * $c->open;
            }

            // Same as above, but for tradeCount
            if ($c->tradeCount > $maxTradeCount) {
                $maxTradeCount = $c->tradeCount;
            }

            // Same as above, but for buyCount
            if ($c->buyCount > $maxBuyCount) {
                $maxBuyCount = $c->buyCount;
            }

            // Same as above, but for sellCount
            if ($c->sellCount > $maxSellCount) {
                $maxSellCount = $c->sellCount;
            }

            // Same as above, but for changedPrice
            if ($c->changedPrice > $maxChangedPrice) {
                $maxChangedPrice = $c->changedPrice;
            }

            // Store new normalized values
            $c->volume = ($c->volume * $c->open)  / $maxVolume;
            $c->buyVolume = ($c->buyVolume * $c->open) / $maxBuyVolume;
            $c->sellVolume = ($c->sellVolume * $c->open) / $maxSellVolume;
            $c->tradeCount = $c->tradeCount / $maxTradeCount;
            $c->buyCount = $c->buyCount / $maxBuyCount;
            $c->sellCount = $c->sellCount / $maxSellCount;
            $c->changedPrice = $c->changedPrice / $maxChangedPrice;
            $c->checked = 1;

            $c->save();
        }
    }

    public function analyzeUptrends()
    {
        $candles = App\Btccandle::where('timestamp', '<=', 1545048720000)->orderBy('id', 'desc')->take(20)->get();
        $afterCandles = App\Btccandle::where('timestamp', '>', 1545048720000)->orderBy('id', 'asc')->take(20)->get();

        foreach ($candles as $key => $c) {
            echo $c->id;
            echo " - ";
            if ($c->open && $c->close) {
                echo $c->open / 6000;
                echo " & ";
                echo $c->close / 6000;
                echo " ";
                echo number_format((1-($c->open / $c->close)) * 100, 2).'%';
            } else {
                echo "N";
            }

            echo " - ";

            if ($c->buyVolume && $c->sellVolume) {
                echo number_format($c->buyVolume / $c->sellVolume, 2);
            } else {
                echo "N";
            }
            echo " - ";
            echo $c->tradeCount;
            echo "<br/>";
        }

        foreach ($afterCandles as $key => $c) {
            echo $c->id;
            echo " - ";
            if ($c->open && $c->close) {
                echo $c->open / 6000;
                echo " & ";
                echo $c->close / 6000;
                echo " ";
                echo number_format((1-($c->open / $c->close)) * 100, 2).'%';
            } else {
                echo "N";
            }

            echo " - ";

            if ($c->buyVolume && $c->sellVolume) {
                echo number_format($c->buyVolume / $c->sellVolume, 2);
            } else {
                echo "N";
            }
            echo " - ";
            echo $c->tradeCount;
            echo "<br/>";
        }
    }

    public function fillEmptyMinutes()
    {
        exit;
        ini_set('memory_limit', '12000M');
        ini_set('max_execution_time', 86400);
        
        $csv = array_map('str_getcsv', file(public_path() . '/new_full_dataset.csv'));
        unset($csv[0]);

        $prevClose = 0;
        $prevPhil1 = 0;
        $prevPhil2 = 0;
        $prevPhil3 = 0;
        $prevPhil4 = 0;
        $prevPhil5 = 0;

        $newDatapoints = [];
        foreach ($csv as $bla) {
            $newDatapoints[$bla[0]] = $bla;
        }

        $expectedDate = '2014-04-02 22:00:00';

        for ($i=0; $i < 99999999999999; $i++) {

            if (array_key_exists($expectedDate, $newDatapoints)) {
                $c = new App\Btccandle;

                $c->date = $newDatapoints[$expectedDate][0];
                $c->open = $newDatapoints[$expectedDate][1];
                $c->close = $newDatapoints[$expectedDate][2];
                $c->high = $newDatapoints[$expectedDate][3];
                $c->low = $newDatapoints[$expectedDate][4];
                $c->volume = $newDatapoints[$expectedDate][5];
                $c->buyVolume  = $newDatapoints[$expectedDate][6];
                $c->sellVolume  = $newDatapoints[$expectedDate][7];
                $c->tradeCount  = $newDatapoints[$expectedDate][8];
                $c->changedPrice  = $newDatapoints[$expectedDate][9];
                $c->rsi_1m = round($newDatapoints[$expectedDate][10], 2);
                $c->rsi_5m = round($newDatapoints[$expectedDate][11], 2);
                $c->rsi_15m = round($newDatapoints[$expectedDate][12], 2);
                $c->rsi_30m = round($newDatapoints[$expectedDate][13], 2);
                $c->rsi_1h = round($newDatapoints[$expectedDate][14], 2);

                $c->save();

                $prevClose = round($newDatapoints[$expectedDate][2], 2);
                $prevPhil1 = round($newDatapoints[$expectedDate][10], 2);
                $prevPhil2 = round($newDatapoints[$expectedDate][11], 2);
                $prevPhil3 = round($newDatapoints[$expectedDate][12], 2);
                $prevPhil4 = round($newDatapoints[$expectedDate][13], 2);
                $prevPhil5 = round($newDatapoints[$expectedDate][14], 2);
            } else {
                $c = new App\Btccandle;

                $c->date = $expectedDate;
                $c->open = $prevClose;
                $c->close = $prevClose;
                $c->high = $prevClose;
                $c->low = $prevClose;
                $c->volume = 0;
                $c->buyVolume  = 0;
                $c->sellVolume  = 0;
                $c->tradeCount  = 0;
                $c->changedPrice  = 0;
                $c->rsi_1m = round($prevPhil1, 2);
                $c->rsi_5m = round($prevPhil2, 2);
                $c->rsi_15m = round($prevPhil3, 2);
                $c->rsi_30m = round($prevPhil4, 2);
                $c->rsi_1h = round($prevPhil5, 2);

                $c->save();
            }

            if ($expectedDate == '2019-01-14 23:59:00') {
                die('DONE');
            }

            $dateTime = \datetime::createfromformat('Y-m-d H:i:s', $expectedDate);
            $expectedDate = $dateTime->modify('+1 minute')->format('Y-m-d H:i:s');
        }

        exit;

        foreach ($newDatapoints as $key => $d) {
            if ($expectedDate != $d[0]) {

                // $dateTime = \datetime::createfromformat('Y-m-d H:i:s', $expectedDate);

                $c = new App\Btccandle;

                $c->date = $expectedDate;
                $c->open = $prevClose;
                $c->close = $prevClose;
                $c->high = $prevClose;
                $c->low = $prevClose;
                $c->volume = 0;
                $c->buyVolume  = 0;
                $c->sellVolume  = 0;
                $c->tradeCount  = 0;
                $c->changedPrice  = 0;
                $c->rsi_1m = $prevPhil1;
                $c->rsi_5m = $prevPhil2;
                $c->rsi_15m = $prevPhil3;
                $c->rsi_30m = $prevPhil4;
                $c->rsi_1h = $prevPhil5;

                $c->save();

                $csv = reset($csv);
            } else {
                $c = new App\Btccandle;
                // $dateTime = \datetime::createfromformat('Y-m-d H:i:s', $d[0]);

                $c->date = $d[0];
                $c->open = $d[1];
                $c->close = $d[2];
                $c->high = $d[3];
                $c->low = $d[4];
                $c->volume = $d[5];
                $c->buyVolume = $d[6];
                $c->sellVolume = $d[7];
                $c->tradeCount = $d[8];
                $c->changedPrice = $d[9];
                $c->rsi_1m = $d[10];
                $c->rsi_5m = $d[11];
                $c->rsi_15m = $d[12];
                $c->rsi_30m = $d[13];
                $c->rsi_1h = $d[14];

                $prevClose = $d[2];
                $prevPhil1 = $d[10];
                $prevPhil2 = $d[11];
                $prevPhil3 = $d[12];
                $prevPhil4 = $d[13];
                $prevPhil5 = $d[14];

                $c->save();
            }

            echo $expectedDate . ' - ' . $d[0];
            echo "<br>";

            $dateTime = \datetime::createfromformat('Y-m-d H:i:s', $expectedDate);
            $expectedDate = $dateTime->modify('+1 minute')->format('Y-m-d H:i:s');
            // echo $dateTime->format('Y-m-d H:i:s');
            // H::pr(strtotime($d[0]));
        }
    }


    public function enabledisable($enabledisable, $coin, $direction)
    {
        exec(sprintf('curl --header "Content-Type: application/json" --request POST --data \'\' http://%s:3002/api/trades/%s/t%sUSD/%s', env('TRADE_IP'), strtolower($enabledisable), strtoupper($coin), strtoupper($direction)));

        return redirect('toggle');
    }

    public function placeTrade($coin, $direction, $price)
    {
        exec(sprintf('curl --header "Content-Type: application/json" --request POST --data \'{"apiKey":"%s","apiSecret":"%s","price":"%s","symbol":"t%sUSD","intent":"%s"}\' http://%s:3002/api/order/new', env('BFX_K'), env('BFX_SC'), $price, strtoupper($coin), strtoupper($direction), env('TRADE_IP')));

        return redirect('trade');
    }    

}