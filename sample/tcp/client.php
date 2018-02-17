<?php

use Esockets\Debug\Log as _;

require __DIR__ . '/../../vendor/autoload.php';

_::setEnv('client');

$work = true;

if (extension_loaded('pcntl')) {
    pcntl_signal(SIGINT, function (int $signo) use (&$work) {
        $work = false;
        _::log('Обработал сигнал: ' . $signo);
    }, false);
}

$configurator = new \Esockets\Base\Configurator(require 'config.php');

$client = $configurator->makeClient();

try {
    $client->connect(new \Esockets\Socket\Ipv4Address('127.0.0.1', '8081'));
    _::log('Успешно соединился!');
} catch (\Esockets\Base\Exception\ConnectionException $e) {
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
stream_set_blocking(STDIN, 0);
while ($work && $client->live()) {
    if ($client->ready()) {
        $client->read();
        usleep(10000);
    } else {
        sleep(1);
    }
    if ($in = fgets(STDIN)) {
        $client->send($in);
        usleep(10000);
    }
    if (extension_loaded('pcntl')) {
        pcntl_signal_dispatch();
    }
}

$client->disconnect();
