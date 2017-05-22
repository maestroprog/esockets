<?php

spl_autoload_register(function ($class) {
    $parts = explode('\\', $class);

    array_shift($parts);

    $path = implode(DIRECTORY_SEPARATOR, $parts);

    $file = stream_resolve_include_path($_file = __DIR__ . DIRECTORY_SEPARATOR . $path . '.php');

    if ($file !== false) {
        require $file;
    }
});
