<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ScrapeTrades extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scrape:btctrades';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        for ($i=0; $i < 30; $i++) {
            sleep(4);
            // $start = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2013-03-31 00:00:00');
            // $end = $start->modify('+10 hours');

            // $request = sprintf('https://api.bitfinex.com/v2/trades/tBTCUSD/hist?start=%s&end=%s&limit=1000&sort=1', $start->getTimestamp() * 1000, $end->getTimestamp() * 1000);
            // H::pr($request);

            $lastTrade = \App\Btctrade::orderBy('id', 'desc')->first();

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

                \DB::statement(sprintf('insert ignore into btctrades (bfx_id, timestamp, date, amount, price, updated_at, created_at) values (%s, %s, "%s", %s, %s, "%s", "%s")', $trade[0], $trade[1], date('Y-m-d H:i:s', $trade[1] / 1000), $trade[2], $trade[3], date('Y-m-d H:i:s', time()), date('Y-m-d H:i:s', time())));

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
}
