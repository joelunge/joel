<?php

class Indicators
{
    public static function rsi($data, $period = 14)
    {
		if($period == 0) return 'error';
		
		$rsi = array();
		$smma_u = 0;
		$smma_d = 0;
		
		for ($i=0;$i<count($data);$i++){
			if($i<$period) {
				$rsi[] = 0;
				continue;
			}
			
			if($data[$i] > $data[$i-1]) {
				$u = $data[$i] - $data[$i-1];
				$d = 0;
			} elseif($data[$i-1] > $data[$i]) {
				$u = 0;
				$d = $data[$i-1] - $data[$i];
			} else {
				$u = 0;
				$d = 0;
			}
			
			$smma_u = ($u + $smma_u * ($period - 1)) / $period;
			$smma_d = ($d + $smma_d * ($period - 1)) / $period;
			if($smma_d == 0) $rsi[] = 100;
			elseif($smma_u == 0) $rsi[] = 0;
			else $rsi[] = round(100 - 100/(1 + $smma_u/$smma_d), 4);
		}
		
		return $rsi;
    }
}