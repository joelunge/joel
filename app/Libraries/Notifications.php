<?php

class Notifications
{
    public static function slack($coins)
    {
    	$message = implode('\r\n', $coins);
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