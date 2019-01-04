<?php

class Csv
{
    public static function readCsv($filepath = false, $delimiter = ',')
    {
    	if ($filepath) {
    		$data = self::parseCsv($filepath, $delimiter);

	    	return true;
    	}

    	echo 'Missing file path';

    	return false;
    }

    private static function parseCsv($filepath, $delimiter = ',')
    {
    	$csvFile = file($filepath);
	    $data = [];
	    foreach ($csvFile as $key => $line) {
			if ($key > 0) {
				$data[] = str_getcsv($line, $delimiter);
			}
	    }

	    self::importCsv($data, $filepath);
    }

    public static function uploadCsv($filepath, $filename, $delimiter = ',')
    {
    	$csvFile = file($filepath);
	    $data = [];
	    foreach ($csvFile as $key => $line) {
			if ($key > 0) {
				$data[] = str_getcsv($line, $delimiter);
			}
	    }

	    self::importCsv($data, $filename);
    }

    private static function importCsv($data, $filename)
    {
    	if (strpos($filename, 'ledger') !== false) {
	    	self::importBalances($data);
	    } elseif (strpos($filename, 'trades') !== false) {
	    	self::importTrades($data);	
	    } else {
	    	die('Not a ledger or trade file');
	    }
    }

    private static function importBalances($data, $userId = false)
    {
    	if (! $userId) {
    		$userId = Auth::id();
    	}
		foreach ($data as $key => $d) {
	    	$hashSource = str_replace('.', '', $d[3]) . strtotime($d[4]);
	    	$data[$key]['hash'] = md5($hashSource);
	    }

	    $data = array_reverse($data);

	    foreach ($data as $key => $d) {
	    	$isAlreadyAdded = App\Balance::where('hash', $d['hash'])->get();

	    	if ($isAlreadyAdded->count() == 0) {
	    		$balance = new App\Balance();
	    		$balance->user_id = $userId;
		    	$balance->hash = $d['hash'];
		    	$balance->currency = $d[0];
		    	$balance->description = $d[1];
		    	$balance->amount = $d[2];
		    	$balance->balance = $d[3];
		    	$balance->date = $d[4];
		    	$balance->save();
	    	}
	    }
    }

    private static function importTrades($data, $userId = false)
    {
    	if (! $userId) {
    		$userId = Auth::id();
    	}
	    foreach ($data as $key => $d) {
	    	$isAlreadyAdded = App\Trade::where('bitfinex_id', $d[0])->get();

	    	if ($isAlreadyAdded->count() == 0) {
	    		$balance = new App\Trade();
		    	$balance->bitfinex_id = $d[0];
		    	$balance->user_id = $userId;
		    	$balance->coin = $d[1];
		    	$balance->amount = $d[2];
		    	$balance->price = $d[3];
		    	$balance->fee = $d[4];
		    	$balance->fee_currency = $d[5];
		    	$balance->date = $d[6];
		    	$balance->save();
	    	}
	    }	
    }
}