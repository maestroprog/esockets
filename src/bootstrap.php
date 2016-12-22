<?php
/**
 * Created by PhpStorm.
 * User: Руслан
 * Date: 22.12.2016
 * Time: 3:42
 */

spl_autoload_register(function ($class) {
    $parts = explode('\\', $class);

    array_shift($parts);

    $path = implode(DIRECTORY_SEPARATOR, $parts);

    $file = stream_resolve_include_path(__DIR__ . DIRECTORY_SEPARATOR . $path . '.php');

    if ($file !== false) {
        require $file;
    }
});
