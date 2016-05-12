<?php
/**
 * Created by PhpStorm.
 * User: yarullin
 * Date: 25.03.2016
 * Time: 20:26
 */

define('INTERVAL', 1000); // 1ms

date_default_timezone_set('Asia/Yekaterinburg');
ini_set('display_errors', true);
error_reporting(E_ALL);
ini_set('log_errors', true);
ini_set('error_log', __DIR__ . '/messages.log');
file_put_contents('messages.log', '');

set_time_limit(0);

spl_autoload_register(function ($class) {
    $parts = explode('\\', $class);

    # Support for non-namespaced classes.
    //$parts[] = str_replace('_', DIRECTORY_SEPARATOR, array_pop($parts));
    $parts = [end($parts)];

    $path = implode(DIRECTORY_SEPARATOR, $parts);

    $file = stream_resolve_include_path('../../src/' . $path . '.php');
    if ($file !== false) {
        require $file;
    }
});


function error_type($type)
{
    switch ($type) {
        case E_ERROR: // 1 //
            return 'E_ERROR';
        case E_WARNING: // 2 //
            return 'E_WARNING';
        case E_PARSE: // 4 //
            return 'E_PARSE';
        case E_NOTICE: // 8 //
            return 'E_NOTICE';
        case E_CORE_ERROR: // 16 //
            return 'E_CORE_ERROR';
        case E_CORE_WARNING: // 32 //
            return 'E_CORE_WARNING';
        case E_COMPILE_ERROR: // 64 //
            return 'E_COMPILE_ERROR';
        case E_COMPILE_WARNING: // 128 //
            return 'E_COMPILE_WARNING';
        case E_USER_ERROR: // 256 //
            return 'E_USER_ERROR';
        case E_USER_WARNING: // 512 //
            return 'E_USER_WARNING';
        case E_USER_NOTICE: // 1024 //
            return 'E_USER_NOTICE';
        case E_STRICT: // 2048 //
            return 'E_STRICT';
        case E_RECOVERABLE_ERROR: // 4096 //
            return 'E_RECOVERABLE_ERROR';
        case E_DEPRECATED: // 8192 //
            return 'E_DEPRECATED';
        case E_USER_DEPRECATED: // 16384 //
            return 'E_USER_DEPRECATED';
    }
    return "";
}

set_exception_handler(function (Exception $e) {
    error_log(sprintf('Вызвана ошибка %d: %s; %s', $e->getCode(), $e->getMessage(), $e->getTraceAsString()));
});

set_error_handler(function ($errno, $errstr, $errfile, $errline, array $errcontext) {
    error_log(sprintf('[%s]: %s in %s at %d line', error_type($errno), $errstr, $errfile, $errline));
});
