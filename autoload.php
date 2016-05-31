<?php
/**
 * Created by PhpStorm.
 * User: yarullin
 * Date: 31.05.2016
 * Time: 21:12
 */
spl_autoload_register(function ($class) {
    $parts = explode('\\', $class);

    # Support for non-namespaced classes.
    //$parts[] = str_replace('_', DIRECTORY_SEPARATOR, array_pop($parts));
    $parts = [end($parts)];

    $path = implode(DIRECTORY_SEPARATOR, $parts);

    $file = stream_resolve_include_path(__DIR__ . '/src/' . $path . '.php');
    if ($file !== false) {
        require $file;
    }
});