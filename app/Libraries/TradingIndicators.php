<?php

class TradingIndicators
{
    public static function GetSMA(array $values, int $length) : float
    {
        if (empty($values) || count($values) < $length || $length == 0) return NAN;
        
        $sumOfPrices = 0;
        $iterator = 0;
        $sma = 0;
        
        $cloned_values = $values;
        
        while (count($values) > 0 && count($values) >= $length - 1)
        {
            foreach ($values as $value)
            {
                $sumOfPrices += $value;
                
                if ($iterator == $length - 1)
                {
                    $sma = $sumOfPrices / $length;
                    
                    break;
                }
                
                $iterator++;
            }
            
            $iterator = 0;
            
            $sumOfPrices = 0;
            
            array_shift($values);
        }
        
        return $sma;
    }
    
    public static function GetCurrentEMA(array $values, int $length) : float
    {
        if (empty($values) || count($values) < $length || $length == 0) return NAN;
        
        $ema = array_reverse(self::GetAllEMA($values, $length));
        
        return $ema[0];
    }
    
    public static function GetMACD(array $listOfPrices, int $fastLength, int $slowLength, 
        int $signal_length)
    {
        if ($listOfPrices == null || count($listOfPrices) == 0 || 
            count($listOfPrices) < $slowLength)
        {
            return NAN;
        }
        
        $emaFast = self::GetAllEMA($listOfPrices, $fastLength);
        $emaSlow = self::GetAllEMA($listOfPrices, $slowLength);
        
        $macdLine = self::GetMacdLine($emaFast, $emaSlow);
        
        if (count($macdLine) == 0) return NAN;
        
        $signal = self::GetAllEMA($macdLine, $signal_length);
        
        if (count($signal) == 0) return NAN;
        
        $histogram = self::GetHistogram($macdLine, $signal);
        
        return array("macd" => $macdLine, "sig" => $signal, "hist"=> $histogram);
    }
    
    //Internal helper functions for calculations
    protected static function GetAllEMA(array $values, int $length) : array
    {
        if (empty($values) || count($values) < $length || $length == 0) return NAN;
        
        $alpha = self::CalculateAlpha($length);
        
        $ema[] = 0;
        
        $iterator = 0;
        
        $prev = 0;
        
        foreach ($values as $value)
        {
            if (!is_numeric($value)) return NAN;
            
            if ($ema[$iterator] == 0)
                $prev = self::GetSMA($values, $length);
            else
                $prev = array_slice($ema, -1)[0];
            
            $ema[] = self::CalculateEMA($value, $alpha, $prev);
            
            $iterator++;
        }
        
        return $ema;
    }
    
    protected static function CalculateEMA(float $currentPrice, float $alpha, float $previousEma)
    {
        if (is_nan($previousEma)) $previousEma = 0;
        
        return $alpha * $currentPrice + (1 - $alpha) * $previousEma;
    }
    
    protected static function CalculateAlpha(int $length)
    {
        return 2 / ($length + 1);
    }
    
    protected static function GetMacdLine(array $fastEma, array $slowEma) : array
    {
        $macdLine = [];
        
        for ($i = 0; $i <= count($fastEma) - 1 && $i <= count($slowEma) - 1; $i++)
        {
            $macdLine[] = $fastEma[$i] - $slowEma[$i];
        }
        
        return $macdLine;
    }
    
    protected static function GetHistogram(array $macdLine, array $signal) : array
    {
        $histogram = [];
        
        //Signal array has 1 extra result. This removes the first result to keep results correct.
        array_shift($signal);
        
        for ($i = 0; $i <= count($macdLine) - 1 && $i <= count($signal) - 1; $i++)
        {
            $histogram[] = $macdLine[$i] - $signal[$i];
        }
        
        return $histogram;
    }
}
?>