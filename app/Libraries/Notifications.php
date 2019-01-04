<?php

class Notifications
{
    public static function slack($coins)
    {
    	$message = '\r\n' . '===================' . '\r\n' . implode('\r\n', $coins) . '\r\n' . '===================' . '\r\n';
    	$webhook = 'https://hooks.slack.com/services/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';

    	exec(sprintf("curl -X POST -H 'Content-type: application/json' --data '{\"text\":\"%s\"}' %s", $message, $webhook));
    }
}