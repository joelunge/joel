<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Btccandle extends Model
{
        public static $returnable = ['id'];

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

        protected $table = 'upw_xrp_1_min';
        // protected $table = 'upw_xrp_1_min';
}