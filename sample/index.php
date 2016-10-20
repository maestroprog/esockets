<?php
/**
 * Created by PhpStorm.
 * User: Руслан
 * Date: 12.12.2015
 * Time: 18:10
 */

require_once 'server-client/common.php';

$server = new \maestroprog\esockets\Server();
if (!$server->connect()) {
    echo ' Не удалось запустить сервер! <br>' . PHP_EOL;
    exit;
}
$server->onConnectPeer(function ($peer) {
    /**
     * @var $peer \maestroprog\esockets\Peer
     */
    \maestroprog\esockets\debug\Log::log(' Принял ' . $peer->getAddress() . ' !');
    $peer->onRead(function ($msg) use ($peer) {
        /**
         * @var $this \maestroprog\esockets\Peer
         */
        \maestroprog\esockets\debug\Log::log(' Получил от ' . $peer->getAddress() . $msg . ' !');
    });
    $peer->onDisconnect(function () use ($peer) {
        \maestroprog\esockets\debug\Log::log('Чувак ' . $peer->getAddress() . ' отсоединиляс от сервера');
    });
});


$client = new maestroprog\esockets\Client();
if ($client->connect()) {
    \maestroprog\esockets\debug\Log::log('успешно соединился!');
}
$client->onDisconnect(function () {
    \maestroprog\esockets\debug\Log::log('Меня отсоединили или я сам отсоединился!');
});
$client->onRead(function ($msg) {
    \maestroprog\esockets\debug\Log::log('Получил что то: ' . $msg . ' !');
});

$work = new \maestroprog\esockets\WorkManager();
$work->addWork('serverAccept', [$server, 'listen'], [], ['always' => true, 'interval' => 5000]);
$work->addWork('serverReceive', [$server, 'read'], [], ['always' => true, 'interval' => 1000]);
$work->addWork('clientReceive', [$client, 'read'], [], ['always' => true, 'interval' => 1000]);

$work->execWork();

if ($client->send('HELLO WORLD!')) {
    \maestroprog\esockets\debug\Log::log('Отправил!');
}
if ($server->send('HELLO!')) {
    \maestroprog\esockets\debug\Log::log('Я тоже отправил!');
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