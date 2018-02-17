<?php

use Esockets\Debug\Log as _;

set_time_limit(0);
require __DIR__ . '/../../vendor/autoload.php';

_::setEnv('server');

$configurator = new \Esockets\Base\Configurator(require 'config.php');

$server = $configurator->makeServer();
try {
    $server->connect(new \Esockets\Socket\Ipv4Address('127.0.0.1', 8081));
    _::log('Сервер слушает сокет');
} catch (\Esockets\Base\Exception\ConnectionException $e) {
    _::log('Не удалось запустить сервер!');
    return;
}

$server->onFound(function (\Esockets\Client $client) {
    _::log('Присоединился новый клиент: ' . $client->getPeerAddress());
    $client->onReceive(function ($data) use ($client) {
        _::log('Получил от клиента ' . $client->getPeerAddress() . ' сообщение: "' . $data . '"');
        $client->send('Hello world!');
    });
    $client->onDisconnect(function () use ($client) {
        _::log('Клиент ' . $client->getPeerAddress() . ' отсоединился');
    });
});

$work = true;

if (extension_loaded('pcntl')) {
    pcntl_signal(SIGINT, function (int $signo) use (&$work) {
        $work = false;
        _::log('Обработал сигнал: ' . $signo);
    }, false);
}

while ($work) {

    $server->find(); // слушаем новые соединения
    if (extension_loaded('pcntl')) {
        pcntl_signal_dispatch();
    }
}
$server->disconnect();
_::log('Успешно завершили работу!');
