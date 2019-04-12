<?php

class Rsi
{
    public static function exec_RSI(array $prices)
	{
		//Set alpha for calculations
		$alpha = 1 / 14;
		return self::calc_RSI($prices, $alpha);     
	}
	
	private static function calc_time_period_change($prices)
	{
		$gain = [];
		$loss = [];
		$remaining_prices = [];
		$last_price = 0;
		for ($i = 1; $i <= count($prices) - 1; $i++)
		{
			if ($i >= 14)
			{
				array_push($remaining_prices, $prices[$i]);
			}
			else
			{
				$result = $prices[$i] - $prices[$i - 1];
				if ($result > 0)
				{
					array_push($gain, $result);
					array_push($loss, 0);
				}
				elseif ($result < 0)
				{
					array_push($gain, 0);
					array_push($loss, abs($result));
				}
				else
				{
					array_push($gain, 0);
					array_push($loss, 0);
				}
				$last_price = $prices[$i];
			}
		}
		return [$gain, $loss, $remaining_prices, $last_price];
	}
	
	private static function calc_time_period_gains_losses($prices)
	{
		$changes = self::calc_time_period_change($prices);
		$gain = $changes[0];
		$loss = $changes[1];
		$remaining_prices = $changes[2];
		$last_price = $changes[3];
		$tot_gain = 0;
		$tot_loss = 0;
		$count_gain = count($gain);
		$count_loss = count($loss);
		foreach ($gain as $gain) { $tot_gain += $gain; }
		foreach ($loss as $loss) { $tot_loss += $loss; }
		return [$tot_gain, $tot_loss, $count_gain, $count_loss, $remaining_prices, $last_price];
	}
	
	private static function calc_time_period_avg_gain_loss($prices)
	{
		$gain_loss_tot = self::calc_time_period_gains_losses($prices);
		$gain = $gain_loss_tot[0];
		$loss = $gain_loss_tot[1];
		$gain_count = $gain_loss_tot[2];
		$loss_count = $gain_loss_tot[3];
		$remaining_prices = $gain_loss_tot[4];
		$last_price = $gain_loss_tot[5];
		$avg_gain = $gain / $gain_count;
		$avg_loss = $loss / $loss_count;
		return [$avg_gain, $avg_loss, $remaining_prices, $last_price];
	}
	
	private static function calc_price_change($remaining_prices, $last_used_price)
	{
		$price_change = [];
		$last_price = $last_used_price;
		for ($i = 0; $i <= count($remaining_prices) - 1; $i++)
		{
			$result = $remaining_prices[$i] - $last_price;
			array_push($price_change, $result);
			$last_price = $remaining_prices[$i];
		}
		return $price_change;
	}
	
	private static function calc_gain_loss($change, $alpha, $last_avg) { return $change * $alpha + (1 - $alpha) * $last_avg; }
	
	private static function calc_avg_gain_loss($prices, $alpha)
	{
		$time_period_avgs = self::calc_time_period_avg_gain_loss($prices);
		$last_avg_gain = $time_period_avgs[0];
		$last_avg_loss = $time_period_avgs[1];
		$remaining_prices = $time_period_avgs[2];
		$last_price = $time_period_avgs[3];
		$avg_gain = 0;
		$avg_loss = 0;
		$price_changes = self::calc_price_change($remaining_prices, $last_price);
		for ($i = 0; $i <= count($price_changes) - 1; $i++)
		{
			if ($price_changes[$i] > 0)
			{
				$avg_loss = self::calc_gain_loss(0, $alpha, $last_avg_loss);
				$avg_gain = self::calc_gain_loss($price_changes[$i], $alpha, $last_avg_gain);
			}
			elseif ($price_changes[$i] < 0)
			{
				$avg_loss = self::calc_gain_loss(abs($price_changes[$i]), $alpha, $last_avg_loss);
				$avg_gain = self::calc_gain_loss(0, $alpha, $last_avg_gain);
			}
			else
			{
				$avg_loss = self::calc_gain_loss(0, $alpha, $last_avg_loss);
				$avg_gain = self::calc_gain_loss(0, $alpha, $last_avg_gain);
			}
			$last_avg_loss = $avg_loss;
			$last_avg_gain = $avg_gain;
		}
		return [$avg_gain, $avg_loss];
	}
	
	private static function calc_RSI($prices, $alpha)
	{
		$averages = self::calc_avg_gain_loss($prices, $alpha);
		$rsi = 0;
		if ($averages[1] == 0)
		{
			$rsi = 100;
		}
		else
		{
			$rs = $averages[0] / $averages[1];
			$rsi = 100 - (100 / (1 + $rs));
		}
		return $rsi;
	}
}