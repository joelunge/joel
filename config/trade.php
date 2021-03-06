<?php

return [
    'indicators' => [
    	'time_frame' => [
    		'1m' => '1m',
			'3m' => '3m',
			'5m' => '5m',
			'10m' => '10m',
			'15m' => '15m',
			'30m' => '30m',
			'1h' => '1h',
			'2h' => '2h',
			'3h' => '3h',
			'4h' => '4h',
			'6h' => '6h',
			'1d' => '1d',
			'1w' => '1w',
    	],
    	'rsi' => [
    		'bearish_div' => 'Bearish Div',
			'bullish_div' => 'Bullish Div',
			'trend_line' => 'Trend Line',
			'overbought_70' => 'Overbought 70+',
			'overbought_80' => 'Overbought 80+',
			'overbought_90' => 'Overbought 90+',
			'oversold_30' => 'Oversold 30-',
			'oversold_20' => 'Oversold 20-',
			'oversold_10' => 'Oversold 10-',
    	],
    	'fib' => [
    		'.0' => '0',
			'.236' => '.236',
			'.382' => '.382',
			'.5' => '.5',
			'.618' => '.618',
			'.65' => '.65',
			'.786' => '.786',
			'.1' => '1',
    	],
    	'ew' => [
    		'1' => '1',
			'2' => '2',
			'3' => '3',
			'4' => '4',
			'5' => '5',
			'a' => 'A',
			'b' => 'B',
			'c' => 'C',
			'd' => 'D',
			'e' => 'E',
    	],
    	'macd' => [
    		'histogram_bearish' => 'Histogram Bearish',
			'histogram_bullish' => 'Histogram Bullish',
			'crossover_down' => 'Crossover Down',
			'crossover_up' => 'Crossover Up',
    	],
    	'trend_lines' => [
    		'ascending_channel' => 'Ascending Channel',
			'descending_channel' => 'Descending Channel',
			'ascending_triangle' => 'Ascending Triangle',
			'descending_triangle' => 'Descending Triangle',
			'symmetric_triangle' => 'Symmetric Triangle',
			'bull_flag' => 'Bull Flag',
			'bear_flag' => 'Bear Flag',
			'bear_wedge' => 'Bear Wedge',
			'bull_wedge' => 'Bull Wedge',
			'support_line' => 'Support Line',
			'resistance_line' => 'Resistance Line',
    	],
    	'ema' => [
    		'crossover_55' => '55 Crossover',
			'crossover_100' => '100 Crossover',
			'crossover_200' => '200 Crossover',
			'13' => '13',
			'21' => '21',
			'55' => '55',
			'100' => '100',
			'200' => '200',
    	],
    	'candles' => [
			'bearish' => 'Bearish',
			'bullish' => 'Bullish',
    	],
    	'price_action' => [
			'bearish' => 'Bearish',
			'bullish' => 'Bullish',
    	],
    	'order_book' => [
    		'bearish' => 'Bearish',
			'bullish' => 'Bullish',
    	],
    	'fundamental' => [
    		'bullish_news' => 'Bullish News',
    		'bearish_news' => 'Bearish News',
    		'bullish_hype' => 'Bullish Tone',
    		'bearish_hype' => 'Bearish Tone',
    		'expecting_reversal' => 'Expecting Reversal',
    	],
    	'distance' => [
    		'5_0_plus' => '+5.0%',
    		'4_5_plus' => '+4.5%',
    		'4_0_plus' => '+4.0%',
    		'3_5_plus' => '+3.5%',
    		'3_0_plus' => '+3.0%',
    		'2_5_plus' => '+2.5%',
    		'2_0_plus' => '+2.0%',
    		'1_5_plus' => '+1.5%',
    		'1_0_plus' => '+1.0%',
    		'0_5_plus' => '+0.5%',
    		'0' => '0',
    		'0_5_minus' => '-0.5%',
    		'1_0_minus' => '-1.0%',
    		'1_5_minus' => '-1.5%',
    		'2_0_minus' => '-2.0%',
    		'2_5_minus' => '-2.5%',
    		'3_0_minus' => '-3.0%',
    		'3_5_minus' => '-3.5%',
    		'4_0_minus' => '-4.0%',
    		'4_5_minus' => '-4.5%',
    		'5_0_minus' => '-5.0%',
    	]
    ],

    'indicator_names' => [
    	'time_frame' => 'Time Frame',
    	'rsi' => 'RSI',
    	'fib' => 'FIB',
    	'ew' => 'EW',
    	'macd' => 'MACD',
    	'trend_lines' => 'Trend Lines',
    	'ema' => 'EMA',
    	'candles' => 'Candles',
    	'price_action' => 'Price Action',
    	'order_book' => 'Order Book',
    	'fundamental' => 'Fundamental',
    	'distance' => 'Distance',
    ],
];