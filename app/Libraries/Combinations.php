<?php

class Combinations
{
	public static function get_combinations($arrays) {
		$result = array(array());
		foreach ($arrays as $property => $property_values) {
			$tmp = array();
			foreach ($result as $result_item) {
				foreach ($property_values as $property_value) {
					$tmp[] = array_merge($result_item, array($property => $property_value));
				}
			}
			$result = $tmp;
		}
		return $result;
	}
}