<?php
/**
 * Created by PhpStorm.
 * User: Руслан
 * Date: 12.12.2015
 * Time: 18:10
 */

use Esockets\debug\Log;

require_once 'tcp/common.php';

$server = new Esockets\TcpServer();
if (!$server->connect()) {
    echo 'Не удалось запустить сервер! <br>' . PHP_EOL;
    exit;
}
$server->onConnectPeer(function ($peer) {
    /**
     * @var $peer \Esockets\Peer
     */
    Log::log('Принял ' . $peer->getAddress() . '!');
    $peer->onRead(function ($msg) use ($peer) {
        /**
         * @var $this \Esockets\Peer
         */
        Log::log('Получил от ' . $peer->getAddress() . ' сообщение: ' . $msg);
    });
    $peer->onDisconnect(function () use ($peer) {
        Log::log('Клиент ' . $peer->getAddress() . ' отсоединился от сервера');
    });
});


$client = new Esockets\TcpClient();
if ($client->connect()) {
    Log::log('Успешно соединился!');
}
$client->onDisconnect(function () {
    Log::log('Меня отсоединили или я сам отсоединился!');
});
$client->onRead(function ($msg) {
    Log::log('Получил что то: ' . $msg);
});

$work = new WorkManager\WorkManager();
$work->addWork('serverLive', [$server, 'live'], [], ['always' => true, 'interval' => 5000]);
$work->addWork('clientReceive', [$client, 'live'], [], ['always' => true, 'interval' => 1000]);

if ($client->send('HELLO WORLD!')) {
    Log::log('Отправил!');
}
if ($server->send('HELLO!')) {
    Log::log('Я тоже отправил!');
}

for ($i = 0; $i < 1000; $i++) {
    $work->execWork();
    usleep(100000);
}

$client->disconnect();
$server->disconnect();

echo 'Окончил работу!', PHP_EOL;
