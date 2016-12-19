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

    public static function log($message)
    {
        if (self::$env) {
            $message = sprintf('{%s} [%s]: %s', self::$env, date('H:i:s'), $message);
        }
        if (PHP_SAPI === 'cli') {
            fputs(STDERR, $message . PHP_EOL);
        } else {
            if (ini_get('log_errors')) {
                error_log($message);
            } else {
                echo $message . '<br>' . PHP_EOL;
            }
        }
    }

    public static function setEnv($env)
    {
        self::$env = $env;
    }
}
