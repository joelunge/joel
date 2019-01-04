<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Trade extends Model
{
	public function scopeExcludeExchangeTrades($query)
	{
		$query = $query->where('bitfinex_id', '!=', '216144900');
		$query = $query->where('bitfinex_id', '!=', '216144904');
		$query = $query->where('bitfinex_id', '!=', '216145205');
		$query = $query->where('bitfinex_id', '!=', '216145209');
		$query = $query->where('bitfinex_id', '!=', '289192249');
		$query = $query->where('bitfinex_id', '!=', '289192252');
		$query = $query->where('bitfinex_id', '!=', '289192265');

		return $query;
	}
}