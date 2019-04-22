<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Btccandle extends Model
{
	protected $fillable = [
                'rsi',
        	'open',
                'high',
                'low',
                'close',
                'tradeCount',
                'buyCount',
                'sellCount',
                'volume',
                'buyVolume',
                'sellVolume',
                'avgAmount',
                'avgBuyAmount',
                'avgSellAmount',
                'standardDev',
                'buyStandardDev',
                'sellStandardDev',
                'changedPrice', 
                'changedPriceUp',
                'changedPriceDown',
                'buyChangedPrice',
                'buyChangedPriceUp',
                'buyChangedPriceDown',
                'sellChangedPrice',
                'sellChangedPriceUp',
                'sellChangedPriceDown',
                'checked',
	];

        protected $table = 'upw_btc_15_min';
}