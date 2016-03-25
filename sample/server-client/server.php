<?php
/**
 * Created by PhpStorm.
 * User: yarullin
 * Date: 25.03.2016
 * Time: 20:26
 */

require 'common.php';

$server = new \Esockets\Server();
$server->open();
if (!$server->open()) {
    echo 'Не удалось запустить сервер!<br>' . PHP_EOL;
    exit;
} else {
    echo 'Сервер слушает сокет<br>' . PHP_EOL;
}
$server->onAccept(function ($peer) {
    /**
     * @var $peer \Esockets\Peer
     */
    error_log(' Принял ' . $peer->getAddress() . ' !');
    $peer->onReceive(function ($msg) use ($peer) {
        /**
         * @var $this \Esockets\Peer
         */
        error_log(' Получил от ' . $peer->getAddress() . $msg . ' !');
    });
    $peer->onDisconnect(function () use ($peer) {
        error_log('Чувак ' . $peer->getAddress() . ' отсоединиляс от сервера');
    });
});

while (true) {

    $server->doAccept(); // принимаем новые соединения
    $server->doReceive(); // принимаем новые сообщения

    usleep(10000); // sleep for 10 ms
}