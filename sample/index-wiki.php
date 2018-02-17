<?php

use Esockets\Base\Configurator;
use Esockets\Socket\SocketFactory;

require_once __DIR__ . '/../vendor/autoload.php'; // подключаем автолоадер

// массив конфигурации общий для сервера и клиента, все опции в конфигурации указаны по умолчанию
$config = [

    Configurator::CONNECTION_TYPE => Configurator::CONNECTION_TYPE_SOCKET, // тип подключения - сокет
    Configurator::CONNECTION_CONFIG => [ // настройки для сокета
        SocketFactory::SOCKET_DOMAIN => AF_INET, // домен сокета
        SocketFactory::SOCKET_PROTOCOL => SOL_TCP, // используемый транспортный протокол
    ],
    Configurator::PROTOCOL_CLASS => \Esockets\Protocol\EasyStream::class, // используемый прикладной протокол
];

// будем слушать порт 8081 на localhost-е
$listenAddress = new \Esockets\Socket\Ipv4Address('127.0.0.1', 8081);

$configurator = new \Esockets\Base\Configurator($config); // инициализируем фабрику

$server = $configurator->makeServer(); // производим настроенный объект сервера с сокетом внутри
$server->block(); // устанавливаем блокирующий режим работы сервера
try {
    $server->connect($listenAddress); // заставляем сервер слушать указанный ip и порт
    echo 'Сервер слушает ' . $listenAddress, PHP_EOL;
} catch (\Esockets\Base\Exception\ConnectionException $e) {
    echo 'Не удалось запустить сервер!', PHP_EOL;
    return;
}
// назначаем обработчик для новых входящих соединений
$server->onFound(function (\Esockets\Client $peer) {
    echo 'Принял входящее соединение ' . $peer->getPeerAddress() . ' !', PHP_EOL;
    // назначаем обработчик для чтения данных от присоединившегося клиента
    $peer->onReceive(function ($msg) use ($peer) {
        echo 'Получил сообщение от ' . $peer->getPeerAddress() . ' ' . var_export($msg, true) . ' !', PHP_EOL;
    });
    // назначаем обработчик отсоединения клиента от сервера
    $peer->onDisconnect(function () use ($peer) {
        echo 'Клиент ' . $peer->getPeerAddress() . ' отсоединился от сервера', PHP_EOL;
    });
});

$client = $configurator->makeClient(); // производим клиента, котороый будет подключаться к серверу
$client->block(); // устанавливаем блокирующий режим работы
try {
    $client->connect($listenAddress); // заставляем клиента подключиться к серверу по указанному адресу
    echo 'Клиент успешно соединился!', PHP_EOL;
} catch (\Esockets\Base\Exception\ConnectionException $e) {
    echo 'Клиент не смог подключиться!', PHP_EOL;
    return;
}

// так как сервер и клиент были переключены в блокирующий режим работы,
// то все операции (соединение, прослушивание, чтение. отправка)
// будут происходить последовательно, дожидаясь успешного завершения операции

// прослушиваем входящие соединения
$server->find(); // метод работает 1 секунду, после чего возвращает управление программе
$client->send(['Hello' => 'World']); // отправим сообщение серверу в виде массива
$server->find(); // сервер снова слушает входящие соединения

echo 'The end', PHP_EOL;
