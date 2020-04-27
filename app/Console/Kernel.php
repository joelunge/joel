<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use UsdToSek;
use Notifications;
use DB;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->call(function () {
        //     UsdToSek::updateUsdToSek();
        // })->hourly();

        $schedule->call(function () {
            \Alerts::alert();
        })->everyMinute()->runInBackground();

        $schedule->call(function () {
            \Order::automaticTarget();
        })->everyMinute();

        $schedule->call(function () {
            $bfx = new \App\Bitfinex(env('BFX_K'), env('BFX_SC'), 'v1');
            $positions = $bfx->get_positions();

            foreach (range(1, 2) as $i) {
                if (! empty($positions)) {
                    \Trade::disableAll();
                }
                sleep(30);
            }
        })->everyMinute();

        $schedule->call(function () {
            $bfx = new \App\Bitfinex(env('BFX_K'), env('BFX_SC'), 'v1');

            foreach (range(1, 5) as $i) {
                $positions = $bfx->get_positions();
                $orders = $bfx->get_orders();

                if (empty($positions) && ! empty($orders)) {
                    $bfx->cancel_all_orders();
                }
                sleep(8);
            }

        })->everyMinute();

        // $schedule->call(function () {
        //     $coins = config('coins.coins');

        //     $actionCoins = [];
        //     foreach ($coins as $coin) {
        //         $avgChangedPrice = DB::connection('mongodb')->table($coin)->avg('changedPrice');
        //         $avgCount = DB::connection('mongodb')->table($coin)->avg('count');
        //         $latest = DB::connection('mongodb')->table($coin)->orderBy('timestamp', 'DESC')->first();

        //         if (time() - ($latest['timestamp'] / 1000) > 120) {
        //             continue;
        //         }

        //         if ($latest['changedPrice'] > ($avgChangedPrice * 7)) {
        //             $actionCoins[] = strtoupper(str_replace('usds', '', str_replace('trades-t', '', $coin)));
        //             $actionCoins[] = 'avgChangedPrice: ' . round($avgChangedPrice);
        //             $actionCoins[] = 'latest: ' . round($latest['changedPrice']);
        //         }

        //         if ($latest['count'] > ($avgCount * 7)) {
        //             $actionCoins[] = strtoupper(str_replace('usds', '', str_replace('trades-t', '', $coin)));
        //             $actionCoins[] = 'avgCount: ' . round($avgCount);
        //             $actionCoins[] = 'latest: ' . round($latest['count']);
        //         }
        //     }

        //     if (count($actionCoins)) {
        //         Notifications::slack($actionCoins);
        //     }

        // })->everyMinute();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
