<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class FormatDataset extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'format:dataset';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    public $rows = 2500;

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
        $fp = fopen('/var/www/crypto.joelunge.site/public/dataset.csv', 'a');
        $this->generateCsvHeader($fp);

    	$candles = \App\Bybit1min::orderBy('open_time', 'ASC')->limit(100000)->get();

    	$dataset = [];
    	foreach ($candles as $key => $c) {
    		$dataset[] = $c->high;
    		$dataset[] = $c->low;
    		$dataset[] = $c->close;

    		if (count($dataset) == 1002) {
    			array_shift($dataset);
    			array_shift($dataset);
    			array_shift($dataset);
    			// \H::pr($dataset);
                // echo implode(',', $dataset).PHP_EOL;
                fwrite($fp, implode(',', $dataset).','.$c->correct_answer2.PHP_EOL);
    		}

    		// USE IMPLODE
    		// \H::pr(implode(',', $dataset));
    	}
        fclose($fp);
    }

    public function handle_old()
    {
        $this->rows = $this->rows-2;
        $fp = fopen('/var/www/crypto.joelunge.site/public/dataset.csv', 'a');
        $this->generateCsvHeader($fp);

        $cs = \App\Bybit1min::where('id', '>', 1491384)->limit(10000)->get();

        $count = 1;
        foreach ($cs as $key => $c) {
            $sqlCommand = sprintf('call sp_pivot_results(%s, %s)', $c->id, $this->rows);
            $candles = \DB::select($sqlCommand);
            $candles = $candles[0];
            $candles = (array) $candles;

            $correctAnswer = $candles['correct_answer2'];
            foreach ($candles as $key => $candle) {
                if (str_contains($key, 'id')) {
                    unset($candles[$key]);
                }

                if (str_contains($key, 'open_datetime')) {
                    unset($candles[$key]);   
                }

                if (str_contains($key, 'open_time')) {
                    unset($candles[$key]);   
                }

                if (str_contains($key, 'open_time')) {
                    unset($candles[$key]);   
                }

                if (str_contains($key, 'answer')) {
                    unset($candles[$key]);   
                }

                if (str_contains($key, '_at')) {
                    unset($candles[$key]);   
                }

            }

            $candles = array_values($candles);

            $string = '';
            foreach ($candles as $key => $value) {
                $string = $string . $value.',';
            }

            $string = $string . $correctAnswer;

            fwrite($fp, $string.PHP_EOL);
            echo $count.PHP_EOL;
            $count = $count+1;
        }

        fclose($fp);
    }

    public function generateCsvHeader($fp)
    {
        $rows = 999;

        $header = '';
        for ($i=1; $i < 1000; $i++) { 
            $header = $header . 'f'.$i.',';
        }

        $header = $header .'correct_answer';

        fwrite($fp, $header.PHP_EOL);
    }
}
