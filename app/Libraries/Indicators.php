<?php

class Indicators
{
    static function rsi($data, $period = 14){
		$change_array = array();
		//loop data
		foreach($data as $key => $row){
			//need 2 points to get change
			if($key >= 1){
				$change = $data[$key]['close'] - $data[$key - 1]['close'];
				//add to front
				array_unshift($change_array, $change);
				//pop back if too long
				if(count($change_array) > $period)
					array_pop($change_array);
			}
			//have enough data to calc rsi
			if($key > $period){
				//reduce change array getting sum loss and sum gains
				$res = array_reduce($change_array, function($result, $item) { 
							if($item >= 0)
								$result['sum_gain'] += $item;
			
							if($item < 0)
								$result['sum_loss'] += abs($item);
					  		return $result; 
						}, array('sum_gain' => 0, 'sum_loss' => 0)); 
				$avg_gain = $res['sum_gain'] / $period;
				$avg_loss = $res['sum_loss'] / $period;
				//check divide by zero
				if($avg_loss == 0){
					$rsi = 100;
				} else {
					//calc and normalize
					$rs = $avg_gain / $avg_loss;				
					$rsi = 100 - (100 / ( 1 + $rs));
				}
				//save
				$data[$key]['val'] = $rsi;
			
			}
		}
		return $data;
	}

    public static function rsi2($data, $period = 14)
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