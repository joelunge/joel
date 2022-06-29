<?php

class YouTube
{
    public static function getHeldForReview()
    {
        // $op = shell_exec('/usr/bin/python3 mgs.py');
        $op = shell_exec('python3 get_held_for_review.py');
        echo $op;
    }
}