<?php
/**
 * Created by PhpStorm.
 * User: yarullin
 * Date: 25.03.2016
 * Time: 20:26
 */

require 'common.php';

use Esockets\debug\Log as _;

$client = new Esockets\TcpClient(['socket_port' => 55667]);
if ($client->connect()) {
    _::log('успешно соединился!');
}
$client->onDisconnect(function () {
    _::log('Меня отсоединили или я сам отсоединился!');
});
$client->onRead(function ($msg) {
    _::log('Получил что то: ' . $msg . ' !');
});

// симулируем увеличение нагрузки
for ($i = 1; $i > 0; $i--) {

    $client->ping();
    usleep($i * 10000);
}

$client->disconnect();
unset($client);

// симулируем множество клиентов
/**
 * @var $clients \Esockets\TcpClient[]
 */
$clients = [];
for ($i = 0; $i < 10; $i++) {

    $client = new Esockets\TcpClient(['socket_port' => 55667]);
    if ($client->connect()) {
        _::log('успешно соединился!');
    }
    $client->onDisconnect(function () {
        _::log('Меня отсоединили или я сам отсоединился!');
    });
    $client->onRead(function ($msg) {
        _::log('Получил что то: ' . $msg . ' !');
    });
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
