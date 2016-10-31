<?php
/**
 * Created by PhpStorm.
 * User: yarullin
 * Date: 21.06.2016
 * Time: 21:13
 */

require_once '../autoload.php';

// массив конфигурации общий для сервера и клиента, все опции в конфигурации указаны по умолчанию
$config = [
    'socket_domain' => AF_INET, // IPv4 протокол (при создании соединения используется TCP), можно изменить на AF_UNIX, если обмен данными будет происходит в пределах одной операционной системы
    'socket_address' => '127.0.0.1', // локальный IP адрес. для AF_UNIX соединения используется путь к файлу сокета
    'socket_port' => '8082', // прослушиваемый порт для входящих соединений (для AF_UNIX)
    'socket_reconnect' => false, // true для автоматического переподключения при обрыве соединения.
];
$server = new \maestroprog\esockets\Server($config);
if (!$server->connect()) {
    echo 'Не удалось запустить сервер!';
    exit;
}
$client = new maestroprog\esockets\Client($config); // передаем конфигурацию, такую же, как для сервера
if ($client->connect()) {
    \maestroprog\esockets\debug\Log::log('успешно соединился!');
}
// назначаем обработчик для новых входящих соединений. при соединении клиента к серверу будет вызван переданный обработчик
$server->onConnectPeer(function ($peer) {
    /**
     * @var $peer \maestroprog\esockets\Peer
     */
    \maestroprog\esockets\debug\Log::log('Принял входящее соединение ' . $peer->getAddress() . ' !');
    // назначаем обработчик для чтения данных от присоединившегося клиента. при получении данных от подключенного клиента будет вызван переданный обработчик
    $peer->onRead(function ($msg) use ($peer) {
        /**
         * @var $this \maestroprog\esockets\Peer
         */
        \maestroprog\esockets\debug\Log::log('Получил сообщение от ' . $peer->getAddress() . ' ' . $msg . ' !');
    });
    // назначаем обработчик для отсоединения клиента от сервера. этот обработчик будет вызван при отсоединении клиента
    $peer->onDisconnect(function () use ($peer) {
        \maestroprog\esockets\debug\Log::log('Клиент ' . $peer->getAddress() . ' отсоединился от сервера');
    });
});

// прослушиваем входящие соединения
$server->listen(); // метод запускает обнаружение новых входящих соединений на сервере
$client->send('HELLO WORLD!'); // метод возвращает true в случае успешной отправки, иначе false

$server->read();