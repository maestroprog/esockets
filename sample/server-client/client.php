<?php
/**
 * Created by PhpStorm.
 * User: yarullin
 * Date: 25.03.2016
 * Time: 20:26
 */

require 'common.php';

$client = new Esockets\Client();
if ($client->connect()) {
    \Esockets\error_log('успешно соединился!');
}
$client->onDisconnect(function () {
    \Esockets\error_log('Меня отсоединили или я сам отсоединился!');
});
$client->onReceive(function ($msg) {
    \Esockets\error_log('Получил что то: ' . $msg . ' !');
});

// симулируем увеличение нагрузки
for ($i = 1; $i > 0; $i--) {

    $client->ping();
    usleep($i*10000);
}

$client->close();
unset($client);

// симулируем множество клиентов

$clients = array();
for ($i = 0; $i < 1; $i++) {

    $client = new Esockets\Client();
    if ($client->connect()) {
        \Esockets\error_log('успешно соединился!');
    }
    $client->onDisconnect(function () {
        \Esockets\error_log('Меня отсоединили или я сам отсоединился!');
    });
    $client->onReceive(function ($msg) {
        \Esockets\error_log('Получил что то: ' . $msg . ' !');
    });
    $clients[$i] = $client;
    usleep(100000);
}
// симулируем большой трафик
for ($i = 0; $i < 1; $i++) {
    foreach ($clients as $j => $client) {
        $client->send('Hello, I am ' . $j . ' client for ' . $i . ' request! =)');
    }
}

// отключаем всех клиентов
foreach ($clients as $client) {
    $client->close();
}