<?php

use Esockets\debug\Log as _;

require 'common.php';

$configurator = new \Esockets\base\Configurator(require 'config.php');

$client = $configurator->makeClient();

try {
    $client->connect(new \Esockets\socket\Ipv4Address('127.0.0.1', '8081'));
    _::log('успешно соединился!');
} catch (\Esockets\base\exception\ConnectionException $e) {
    _::log('не соединился');
}
$client->onDisconnect(function () {
    _::log('Меня отсоединили или я сам отсоединился!');
});
$client->onReceive(function ($msg) {
    _::log('Получил что то: ' . $msg . ' !');
});
/*
// симулируем увеличение нагрузки
for ($i = 1; $i > 0; $i--) {
    $client->ping();
    usleep($i * 10000);
}*/

$client->disconnect();
unset($client);

// симулируем множество клиентов

$clients = [];
for ($i = 0; $i < 10; $i++) {

    $client = $configurator->makeClient();

    try {
        $client->connect(new \Esockets\socket\Ipv4Address('127.0.0.1', '8081'));
        _::log('успешно соединился!');
    } catch (\Esockets\base\exception\ConnectionException $e) {
        _::log('не соединился');
    }

    $client->onDisconnect(function () {
        _::log('Меня отсоединили или я сам отсоединился!');
    })->subscribe();
    $client->onReceive(function ($msg) {
        _::log('Получил что то: ' . $msg . ' !');
    })->subscribe();
    $clients[$i] = $client;
    usleep(100000);
}
// эмулируем большой трафик
for ($i = 0; $i < 10; $i++) {
    foreach ($clients as $j => $client) {
        if (!$client->send('Hello, I am ' . $j . ' client for ' . $i . ' request! =)')) {
            _::log('FAIL SEND');
        }
        unset($client);
    }
    usleep(100000);
}
sleep(1);
// отключаем всех клиентов
foreach ($clients as $client) {
    $client->disconnect();
}
