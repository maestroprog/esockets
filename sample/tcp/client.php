<?php

use Esockets\debug\Log as _;

require __DIR__ . '/../../src/bootstrap.php';

_::setEnv('client');

$configurator = new \Esockets\base\Configurator(require 'config.php');

$client = $configurator->makeClient();

try {
    $client->connect(new \Esockets\socket\Ipv4Address('127.0.0.1', '8081'));
    _::log('Успешно соединился!');
} catch (\Esockets\base\exception\ConnectionException $e) {
    _::log('Не соединился');
    return;
}
$client->onDisconnect(function () {
    _::log('Меня отсоединили или я сам отсоединился!');
});
$client->onReceive(function ($msg) use ($client) {
    _::log('Получил сообщение от сервера: "' . $msg . '"');
});

$client->send('Hello!');
while ($client->live()) {
    $client->read();
    sleep(1);
}

$client->disconnect();
