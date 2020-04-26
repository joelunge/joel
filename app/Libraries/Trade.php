<?php

class Trade
{
    public static function enable($coin, $direction)
    {
    	exec(sprintf('curl --header "Content-Type: application/json" --request POST --data \'\' http://%s:3002/api/trades/%s/t%sUSD/%s', env('TRADE_IP'), 'enable', strtoupper($coin), strtoupper($direction)));
    }

    public static function disable($coin, $direction)
    {
    	exec(sprintf('curl --header "Content-Type: application/json" --request POST --data \'\' http://%s:3002/api/trades/%s/t%sUSD/%s', env('TRADE_IP'), 'disable', strtoupper($coin), strtoupper($direction)));
    }

    public static function disableAll()
    {
    	self::disable('btc', 'buy');
        self::disable('eos', 'buy');
        self::disable('xrp', 'buy');
        self::disable('etc', 'buy');
        self::disable('eth', 'buy');
        self::disable('iot', 'buy');
        self::disable('ltc', 'buy');
        self::disable('neo', 'buy');
        self::disable('btc', 'sell');
        self::disable('eos', 'sell');
        self::disable('xrp', 'sell');
        self::disable('etc', 'sell');
        self::disable('eth', 'sell');
        self::disable('iot', 'sell');
        self::disable('ltc', 'sell');
        self::disable('neo', 'sell');
    }

    public static function enableAll()
    {
    	self::enable('btc', 'buy');
		self::enable('eos', 'buy');
		self::enable('xrp', 'buy');
		self::enable('etc', 'buy');
		self::enable('eth', 'buy');
		self::enable('iot', 'buy');
		self::enable('ltc', 'buy');
		self::enable('neo', 'buy');
		self::enable('btc', 'sell');
		self::enable('eos', 'sell');
		self::enable('xrp', 'sell');
		self::enable('etc', 'sell');
		self::enable('eth', 'sell');
		self::enable('iot', 'sell');
		self::enable('ltc', 'sell');
		self::enable('neo', 'sell');    	
    }
}