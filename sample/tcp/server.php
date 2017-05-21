<?php

use Esockets\debug\Log as _;

require 'common.php';

$configurator = new \Esockets\base\Configurator(require 'config.php');

$server = $configurator->makeServer();
try {
    $server->connect(new \Esockets\socket\Ipv4Address('127.0.0.1', 8081));
    _::log('Сервер слушает сокет');
} catch (\Esockets\base\exception\ConnectionException $e) {
    _::log('Не удалось запустить сервер!');
    return;
}
$server->onFound(function (\Esockets\Client $client) {
    _::log('Принял ' . $client->getPeerAddress() . '!');
    $client->onReceive(function ($data) use ($client) {
        //_::log('Получил от ' . $client->getPeerAddress() . ': "' . $data . '"!');
        $client->send('OK' . $data);
    })->subscribe();
    $client->onDisconnect(function () use ($client) {
        _::log('Пир ' . $client->getPeerAddress() . ' отсоединился от сервера');
    })->subscribe();
})->subscribe();

$work = true;

if (extension_loaded('pcntl')) {
    pcntl_signal(SIGINT, function (int $signo) use (&$work) {
        $work = false;
        var_dump($signo);
    }, false);
}

while ($work) {

    $server->find(); // слушаем новые соединения
    $server->read(); // принимаем новые сообщения

    /*if (time() % 1000 === 0) {
        $server->ping();
    }*/

    usleep(5000); // sleep for 10 ms
    if (extension_loaded('pcntl')) {
        pcntl_signal_dispatch();
    }
}
$server->disconnect();
_::log('Успешно завершили работу!');
