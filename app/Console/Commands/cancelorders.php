<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class cancelorders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:cancelAll';

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
        \Log::debug('orders:cancelAll - start');
        $bfx = new \App\Bitfinex(env('BFX_K'), env('BFX_SC'), 'v1');

        foreach (range(1, 5) as $i) {
            $positions = $bfx->get_positions();
            $orders = $bfx->get_orders();

            if (empty($positions) && ! empty($orders)) {
                $bfx->cancel_all_orders();
            }
            sleep(8);
        }
    }
}
