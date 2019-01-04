<?php

class H
{
    public static function br($rowsAfter = 1, $content = '', $rowsBefore = 0)
    {
        for ($i = 0; $i < $rowsBefore; $i ++) { 
            echo "<br />";
        }
        echo $content;
        for ($i = 0; $i < $rowsAfter; $i ++) { 
            echo "<br />";
        }
    }

    public static function pr($var, $exit = true)
    {
    	echo "<pre>";
    	print_r($var);
    	echo "</pre>";

        if ($exit) {
            exit;
        }
    }

    public static function pre()
    {
        echo "<pre>";
    }

    public static function vd($var)
    {
        var_dump($var);
    }
}