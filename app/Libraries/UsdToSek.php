<?php

class UsdToSek
{
    public static function updateUsdToSek()
    {
    	$usdToSekPrice = shell_exec('python '.public_path() . '/scrape.py');

    	$currentUsdToSek = App\Currency::find(1);
    	$currentUsdToSek->value = (double)$usdToSekPrice;
    	$currentUsdToSek->save();
    }
}