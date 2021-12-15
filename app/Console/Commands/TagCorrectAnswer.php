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
    protected $signature = 'tag:correctanswer';

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
        while (1) {
            $lastWithAnswer = \App\Bybit1min::whereNotNull('correct_answer')->orderBy('open_time', 'DESC')->first();
            $candles = \App\Bybit1min::where('id', '>', $lastWithAnswer->id)->orderBy('open_time', 'ASC')->limit('1000')->get();

            $entryCandle = $candles[0];

            $entryPrice = $entryCandle->close;
            $targetProfit = round($entryPrice * 1.005, 2);
            $stopLoss = round($entryPrice * 0.995, 2);

            $correctAnswer = NULL;
            foreach ($candles as $key => $candle) {
                if ($key != 0) {
                    if ($candle->low <= $stopLoss) {
                        $correctAnswer = 0;
                        break;
                    } elseif ($candle->close > $targetProfit) {
                        $correctAnswer = 1;
                        break;
                    }
                }
            }

            $entryCandle->correct_answer = $correctAnswer;
            $entryCandle->save();
        }
    }
}
