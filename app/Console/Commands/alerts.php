<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class alerts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'alerts:alert';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run alerts';

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
        \Alerts::alert();
    }
}
