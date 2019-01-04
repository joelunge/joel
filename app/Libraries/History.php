<?php

class History
{
    public static function trades($ticker, $start, $end, $limit = 1000, $sort = -1)
    {
    	$requestUrl = sprintf('https://api.bitfinex.com/v2/trades/t%s/hist?limit=%s&start=%s&end=%s&sort=%s', $ticker, $limit, $start, $end, $sort);

    	$content = file_get_contents($requestUrl);

    	return $content;
    }
}