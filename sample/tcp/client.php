<?php
/**
 * Created by PhpStorm.
 * User: yarullin
 * Date: 25.03.2016
 * Time: 20:26
 */
/* todo этот скрипт отрабатывает так быстро, что класс
Net не успевает принять данные, как обнаруживает дисконнект.
Т.Е. где-то бага, но там есть todo-шки, так что надо все-таки разобраться как это работает :)
Возможно, написать тест для выяснения всех возможных ошибок/дисконнектов.
*/
require 'common.php';

use maestroprog\esockets\debug\Log as _;

$client = new maestroprog\esockets\TcpClient(['socket_port' => 55667]);
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
 * @var $clients \maestroprog\esockets\TcpClient[]
 */
$clients = [];
for ($i = 0; $i < 10; $i++) {

    $client = new maestroprog\esockets\TcpClient(['socket_port' => 55667]);
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
// симулируем большой трафик
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
