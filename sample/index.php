<?php
/**
 * Created by PhpStorm.
 * User: Руслан
 * Date: 12.12.2015
 * Time: 18:10
 */
/*
spl_autoload_register(function ($class) {
    $parts = explode('\\', $class);

    # Support for non-namespaced classes.
    //$parts[] = str_replace('_', DIRECTORY_SEPARATOR, array_pop($parts));
    $parts = [end($parts)];

    $path = implode(DIRECTORY_SEPARATOR, $parts);

    $file = stream_resolve_include_path('../src/' . $path . '.php');
    if ($file !== false) {
        require $file;
    }
});*/

require '../autoload.php';

define('INTERVAL', 1000); // 1ms

date_default_timezone_set('Asia/Yekaterinburg');
ini_set('display_errors', true);
error_reporting(E_ALL);
ini_set('log_errors', true);
ini_set('error_log', __DIR__ . '/messages.log');
file_put_contents('messages.log', '');


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

$server = new \Esockets\Server();
if (!$server->connect()) {
    echo ' Не удалось запустить сервер! <br>' . PHP_EOL;
    exit;
}
$server->onConnectPeer(function ($peer) {
    /**
     * @var $peer \Esockets\Peer
     */
    error_log(' Принял ' . $peer->getAddress() . ' !');
    $peer->onRead(function ($msg) use ($peer) {
        /**
         * @var $this \Esockets\Peer
         */
        error_log(' Получил от ' . $peer->getAddress() . $msg . ' !');
    });
    $peer->onDisconnect(function () use ($peer) {
        error_log('Чувак ' . $peer->getAddress() . ' отсоединиляс от сервера');
    });
});


$client = new Esockets\Client();
if ($client->connect()) {
    error_log('успешно соединился!');
}
$client->onDisconnect(function () {
    error_log('Меня отсоединили или я сам отсоединился!');
});
$client->onRead(function ($msg) {
    error_log('Получил что то: ' . $msg . ' !');
});

$work = new \Esockets\WorkManager();
$work->addWork('serverAccept', [$server, 'listen'], [], ['always' => true, 'interval' => 5000]);
$work->addWork('serverReceive', [$server, 'read'], [], ['always' => true, 'interval' => 1000]);
$work->addWork('clientReceive', [$client, 'read'], [], ['always' => true, 'interval' => 1000]);

$work->execWork();

if ($client->send('HELLO WORLD!')) {
    error_log('Отправил!');
}
if ($server->send('HELLO!')) {
    error_log('Я тоже отправил!');
}

for ($i = 0; $i < 2; $i++) {
    $work->execWork();
    sleep(1);
}
$work->deleteWork('serverReceive');

$server->disconnect();

for ($i = 0; $i < 2; $i++) {
    $work->execWork();
    sleep(1);
}

echo ' Окончил работу!<br>' . PHP_EOL;