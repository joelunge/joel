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
        $schedule->call(function () {
            UsdToSek::updateUsdToSek();
        })->hourly();

        $schedule->call(function () {
            $coins = [
                'trades-tbatusds',
                'trades-tbchusds',
                'trades-tbftusds',
                'trades-tbtcusds',
                'trades-tbtgusds',
                'trades-tdaiusds',
                'trades-tdshusds',
                'trades-tdthusds',
                'trades-tedousds',
                'trades-telfusds',
                'trades-teosusds',
                'trades-tetcusds',
                'trades-tethusds',
                'trades-tetpusds',
                'trades-tgntusds',
                'trades-tiotusds',
                'trades-tltcusds',
                'trades-tlymusds',
                'trades-tneousds',
                'trades-tomgusds',
                'trades-tqtmusds',
                'trades-tsanusds',
                'trades-ttrxusds',
                'trades-txlmusds',
                'trades-txmrusds',
                'trades-txrpusds',
                'trades-txtzusds',
                'trades-txvgusds',
                'trades-tzecusds',
                'trades-tzrxusds',
            ];

            $actionCoins = [];
            foreach ($coins as $coin) {
                $avgChangedPrice = DB::connection('mongodb')->table($coin)->avg('changedPrice');
                $avgCount = DB::connection('mongodb')->table($coin)->avg('count');
                $latest = DB::connection('mongodb')->table($coin)->orderBy('timestamp', 'DESC')->first();

                if (time() - ($latest['timestamp'] / 1000) > 120) {
                    continue;
                }

                if ($latest['changedPrice'] > ($avgChangedPrice * 7)) {
                    $actionCoins[] = strtoupper(str_replace('usds', '', str_replace('trades-t', '', $coin)));
                    $actionCoins[] = 'avgChangedPrice: ' . round($avgChangedPrice);
                    $actionCoins[] = 'latest: ' . round($latest['changedPrice']);
                }

                if ($latest['count'] > ($avgCount * 7)) {
                    $actionCoins[] = strtoupper(str_replace('usds', '', str_replace('trades-t', '', $coin)));
                    $actionCoins[] = 'avgCount: ' . round($avgCount);
                    $actionCoins[] = 'latest: ' . round($latest['count']);
                }
            }

            if (count($actionCoins)) {
                Notifications::slack($actionCoins);
            }

        })->everyMinute();
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
