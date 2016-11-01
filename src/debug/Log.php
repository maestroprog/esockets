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
    protected static $env;

    public static function log($msg)
    {
        if (self::$env) {
            $msg = sprintf('{%s}: %s', self::$env, $msg);
        }
        echo $msg . PHP_EOL;
        \error_log($msg);
    }

    public static function setEnv($env)
    {
        self::$env = $env;
    }
}