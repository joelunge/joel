<?php

class Csv
{
    public static function readCsv($filepath = false, $delimiter = ',')
    {
    	if ($filepath) {
    		$data = self::parseCsv($filepath, $delimiter);		
	    	H::pr($data);

	    	return true;
    	}

    	echo 'Missing file path';

    	return false;
    }

    private static function parseCsv($filepath, $delimiter)
    {
    	$csvFile = file($filepath);
	    $data = [];
	    foreach ($csvFile as $line) {
	        $data[] = str_getcsv($line, $delimiter);
	    }

	    $data = array_map(function($test) {
	    	echo utf8_encode($test[1]);
	    	H::br();
	    	// return $test[0];
	    }, $data);

	    H::pr($data);
	    exit;

	    // H::pr($headers);
	    // return array_combine($headers, $data);
    }
}