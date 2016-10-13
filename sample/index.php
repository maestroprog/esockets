<?php
/**
 * Created by PhpStorm.
 * User: Руслан
 * Date: 12.12.2015
 * Time: 18:10
 */

require_once 'server-client/common.php';

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