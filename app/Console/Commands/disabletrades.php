<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class disabletrades extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'trades:disableAll';

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
        \Log::debug('trades:disableAll - start');
        $bfx = new \App\Bitfinex(env('BFX_K'), env('BFX_SC'), 'v1');
        $positions = $bfx->get_positions();

        foreach (range(1, 2) as $i) {
            if (! empty($positions)) {
                \Trade::disableAll();
            }
            sleep(30);
        }
    }
}
