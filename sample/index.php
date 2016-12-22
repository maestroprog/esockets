<?php
/**
 * Created by PhpStorm.
 * User: Руслан
 * Date: 12.12.2015
 * Time: 18:10
 */

require_once 'tcp/common.php';

$server = new \Esockets\TcpServer();
if (!$server->connect()) {
    echo ' Не удалось запустить сервер! <br>' . PHP_EOL;
    exit;
}
$server->onConnectPeer(function ($peer) {
    /**
     * @var $peer \Esockets\Peer
     */
    \Esockets\debug\Log::log(' Принял ' . $peer->getAddress() . ' !');
    $peer->onRead(function ($msg) use ($peer) {
        /**
         * @var $this \Esockets\Peer
         */
        \Esockets\debug\Log::log(' Получил от ' . $peer->getAddress() . $msg . ' !');
    });
    $peer->onDisconnect(function () use ($peer) {
        \Esockets\debug\Log::log('Чувак ' . $peer->getAddress() . ' отсоединиляс от сервера');
    });
});


$client = new Esockets\TcpClient();
if ($client->connect()) {
    \Esockets\debug\Log::log('успешно соединился!');
}
$client->onDisconnect(function () {
    \Esockets\debug\Log::log('Меня отсоединили или я сам отсоединился!');
});
$client->onRead(function ($msg) {
    \Esockets\debug\Log::log('Получил что то: ' . $msg . ' !');
});

$work = new \Esockets\WorkManager();
$work->addWork('serverAccept', [$server, 'listen'], [], ['always' => true, 'interval' => 5000]);
$work->addWork('serverReceive', [$server, 'read'], [], ['always' => true, 'interval' => 1000]);
$work->addWork('clientReceive', [$client, 'read'], [], ['always' => true, 'interval' => 1000]);

$work->execWork();

if ($client->send('HELLO WORLD!')) {
    \Esockets\debug\Log::log('Отправил!');
}
if ($server->send('HELLO!')) {
    \Esockets\debug\Log::log('Я тоже отправил!');
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