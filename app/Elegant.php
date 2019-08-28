<?php

use Illuminate\Database\Eloquent\Model;

abstract class Elegant extends Model
{
    public static $returnable = [];

    public static function all()
    {
        return $this->get(static::$returnable)->all();
    }
}