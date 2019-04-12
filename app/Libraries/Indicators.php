<?php

class Indicators
{
    public static function Rsi($ids, $prices)
    {
    	return \Rsi::exec_RSI($ids, $prices);
    }
}