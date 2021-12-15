<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TagCorrectAnswer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'book:keeping';

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
        $commissions = \App\Commission::whereBetween('datetime', ['2021-01-01 00:00:00', '2021-12-31 23:59:59'])->get();

        $commissionsArr = [];
        $coins = [];

        foreach ($commissions as $key => $commission) {
            $coin = $commission->coin;

            // if ($coin != 'USDT') {
                $coins[$coin] = 0;
            // }
        }

        foreach ($commissions as $key => $commission) {
            $ym = date('Y-m', strtotime($commission->datetime));
            $ymd = date('Y-m-d', strtotime($commission->datetime));
            $coin = $commission->coin;
            $c = $commission->commissions;

            if ($coin != 'USDT') {
                // \H::pr($coin);

                $usdPrice = \App\Allbybitcoin::where('open_datetime', 'LIKE', '%'.$ymd.'%')->where('coin', $coin)->first();

                if (! $usdPrice) {
                    $usdPrice = \App\Allbybitcoin::where('coin', $coin)->orderBy('open_datetime', 'ASC')->first();
                }

                $coins[$coin] = $coins[$coin] + ($c * $usdPrice->close);
            } else {
                $coins[$coin] = $coins[$coin] + $c;
            }
        }

        \H::pr($coins);

        // Loopa igenom alla commissions igen och räkna ut USD -> SEK pris baserat på rätt datum och sedan spara i arrayen
    }

    public function handle2()
    {
        // GET ALL COINS
        $commissions = \App\Commission::get();

        $commissionsArr = [];
        $coins = [];

        foreach ($commissions as $key => $commission) {
            $coin = explode(' ', $commission->commissions)[1];

            if ($coin != 'USDT') {
                $coins[$coin] = $coin;
            }
        }

        \H::pr($coins);
    }
}
