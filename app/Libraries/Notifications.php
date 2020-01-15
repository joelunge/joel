<?php

class Notifications
{
    public static function slack($coins)
    {
    	$message = implode('\r\n', $coins);
    	$webhook = env('SLACK_URL');

    	exec(sprintf("curl -X POST -H 'Content-type: application/json' --data '{\"text\":\"%s\"}' %s", $message, $webhook));
    }

    public static function slackMessage($message)
    {
    	$message = $message;
    	$webhook = env('SLACK_URL');

    	exec(sprintf("curl -X POST -H 'Content-type: application/json' --data '{\"text\":\"%s\"}' %s", $message, $webhook));
    }
}