<?php

/**
 * Created by PhpStorm.
 * User: yarullin
 * Date: 20.10.16
 * Time: 20:45
 */

namespace maestroprog\esockets\debug;

final class Log
{
    public static function log($msg)
    {
        echo $msg . PHP_EOL;
        \error_log($msg);
    }
}