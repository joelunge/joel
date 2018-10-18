<?php

class Notifications
{
    public static function slack($coins)
    {
    	$message = implode('\r\n', $coins) . 'https://www.tradingview.com/chart/xBDH50cD/';
    	$webhook = 'https://hooks.slack.com/services/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';

    	exec(sprintf("curl -X POST -H 'Content-type: application/json' --data '{\"text\":\"%s\"}' %s", $message, $webhook));
    }
}