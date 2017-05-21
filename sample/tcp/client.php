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

//$client->disconnect();
//unset($client);

// симулируем множество клиентов

/**
 * @var $clients \Esockets\Client[]
 */
$clients = [$client];
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
sleep(1);
// эмулируем большой трафик
$time = microtime(true);
$send = 0;
for ($i = 0; $i < 1000; $i++) {
    foreach ($clients as $j => $client) {
        if (!$client->send('Hello, I am ' . $j . ' client for ' . $i . ' request! =)Hello, I am ' . $j . ' client for ' . $i . ' request! =)')) {
            _::log('FAIL SEND');
        } else {
            $send++;
        }
    }
    //usleep(100000);
}
_::log($send);
$persist = [];
$packetsr = $bytesr = 0;
$packets = $bytes = 0;
while (count($clients) > 0) {
    foreach ($clients as $i => $client) {
        if (substr($client->returnRead(), 0, 2) === 'OK') {
            if (++$persist[$client->getClientAddress()->__toString()] === 1000) {
                $client->disconnect();
                $stat = $client->getStatistic();
                $packetsr += $stat->getReceivedPacketsCount();
                $bytesr += $stat->getReceivedBytesCount();
                $packets += $stat->getTransmittedPacketsCount();
                $bytes += $stat->getTransmittedBytesCount();
                unset($clients[$i]);
            }
        }
    }
    usleep(5000);
}
_::log(microtime(true) - $time);
sleep(1);
// отключаем всех клиентов
foreach ($clients as $client) {
    $client->disconnect();
}

echo sprintf('R p: %d, b: %d K; T p: %d, b: %d K', $packetsr, round($bytesr / 1024), $packets, round($bytes / 1024));
