<?php

class Notifications
{
    public static function slack($coins)
    {
    	$message = '\r\n' . '===================' . '\r\n' . implode('\r\n', $coins) . '\r\n' . '===================' . '\r\n';
    	$webhook = 'https://hooks.slack.com/services/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';

    	exec(sprintf("curl -X POST -H 'Content-type: application/json' --data '{\"text\":\"%s\"}' %s", $message, $webhook));
    }

    public static function slackMessage($message)
    {
    	$message = $message;
    	$webhook = 'https://hooks.slack.com/services/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';

    	exec(sprintf("curl -X POST -H 'Content-type: application/json' --data '{\"text\":\"%s\"}' %s", $message, $webhook));
    }
}