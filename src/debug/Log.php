<?php

namespace Esockets\debug;

final class Log
{
    protected static $env;

    public static function log(...$message)
    {
        if (self::$env) {
            $message = sprintf(
                '{%s} [%s].[%4f]: %s',
                self::$env,
                date('H:i:s'),
                microtime(true) - time(),
                implode(' , ', $message)
            );
        }
        if (PHP_OS === 'WINNT') {
            if (ini_get('log_errors')) {
                error_log($message);
            } else {
                echo $message . '<br>' . PHP_EOL;
            }
        } elseif (PHP_SAPI === 'cli') {
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
