<?php
/**
 * Created by PhpStorm.
 * User: yarullin
 * Date: 25.03.2016
 * Time: 20:26
 */

require 'common.php';
$work = true;

if (extension_loaded('pcntl')) {
    pcntl_signal(SIGINT, function (int $signo) use (&$work) {
        $work = false;
        var_dump($signo);
    }, false);
}

use Esockets\debug\Log as _;

$server = new \Esockets\TcpServer(['socket_port' => 55667]);
if (!$server->connect()) {
    echo 'Не удалось запустить сервер!<br>' . PHP_EOL;
    exit;
} else {
    echo 'Сервер слушает сокет<br>' . PHP_EOL;
}
$server->onConnectPeer(function ($peer) {
    /**
     * @var $peer \Esockets\Peer
     */
    _::log('Принял ' . $peer->getAddress() . ' !');
    $peer->onRead(function ($msg) use ($peer) {
        /**
         * @var $this \Esockets\Peer
         */
        _::log('Получил от ' . $peer->getAddress() . $msg . ' !');
    });
    $peer->onDisconnect(function () use ($peer) {
        _::log('Пир ' . $peer->getAddress() . ' отсоединился от сервера');
    });
});

while ($work) {

    $server->listen(); // слушаем новые соединения
    $server->read(); // принимаем новые сообщения
    if (time() % 1000 === 0) {
        $server->ping();
    }

    usleep(10000); // sleep for 10 ms
    if (extension_loaded('pcntl')) {
        pcntl_signal_dispatch();
    }
}
$server->disconnect();
echo 'Успешно завершили работу!', PHP_EOL;
