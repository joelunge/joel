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

        $schedule->command('orders:automaticTarget')->everyMinute()->runInBackground();
        $schedule->command('alerts:alert')->everyMinute()->runInBackground();
        $schedule->command('trades:disableAll')->everyMinute()->runInBackground();
        $schedule->command('orders:cancelall')->everyMinute()->runInBackground();

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
