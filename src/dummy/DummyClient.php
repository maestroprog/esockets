<?php

namespace Esockets\dummy;

use Esockets\base\AbstractAddress;
use Esockets\base\AbstractClient;
use Esockets\base\exception\ReadException;
use Esockets\base\IoAwareInterface;
use Esockets\base\PingPacket;

/**
 * Класс-заглушка ввода-вывода.
 */
final class Dummy extends AbstractClient implements IoAwareInterface
{
    /**
     * @inheritDoc
     */
    public function read()
    {
        // TODO: Implement read() method.
    }

    /**
     * @inheritDoc
     */
    public function returnRead()
    {
        // TODO: Implement returnRead() method.
    }

    /**
     * @inheritDoc
     */
    public function needRead(int $length)
    {
        // TODO: Implement needRead() method.
    }

    /**
     * @inheritDoc
     */
    public function onReceive(callable $callback)
    {
        // TODO: Implement onReceive() method.
    }

    public function getReceivedBytesCount(): int
    {
        // TODO: Implement getReceivedBytesCount() method.
    }

    public function getReceivedPacketCount(): int
    {
        // TODO: Implement getReceivedPacketCount() method.
    }

    public function send($data): bool
    {
        // TODO: Implement send() method.
    }

    public function getTransmittedBytesCount(): int
    {
        // TODO: Implement getTransmittedBytesCount() method.
    }

    public function getTransmittedPacketCount(): int
    {
        // TODO: Implement getTransmittedPacketCount() method.
    }

    /**
     * Вернет адрес сервера, к которому подключени клиент.
     *
     * @return AbstractAddress
     */
    public function getServerAddress(): AbstractAddress
    {
        // TODO: Implement getServerAddress() method.
    }

    /**
     * Вернет адрес клиента, который подключен к серверу.
     *
     * @return AbstractAddress
     */
    public function getClientAddress(): AbstractAddress
    {
        // TODO: Implement getClientAddress() method.
    }

    public function reconnect(): bool
    {
        // TODO: Implement reconnect() method.
    }

    /**
     * Поддерживает жизнь соединения.
     * Что делает:
     * - контролирует текущее состояние соединения,
     * - проверяет связь с заданным интервалом,
     * - выполняет чтение входящих данных,
     * - выполняет переподключение при обрыве связи, если это включено,
     *
     * Возвращает true, если сокет жив, false если не работает.
     * Можно использовать в бесконечном цикле:
     * while ($NET->live()) {
     *     // тут делаем что-то.
     * }
     *
     * @return bool
     */
    public function live()
    {
        // TODO: Implement live() method.
    }

    /**
     * @param AbstractAddress $address
     * @return void
     */
    public function connect(AbstractAddress $address)
    {
        // TODO: Implement connect() method.
    }

    public function onConnect(callable $callback)
    {
        // TODO: Implement onConnect() method.
    }

    public function isConnected(): bool
    {
        // TODO: Implement isConnected() method.
    }

    public function disconnect()
    {
        // TODO: Implement disconnect() method.
    }

    public function onDisconnect(callable $callback)
    {
        // TODO: Implement onDisconnect() method.
    }

    public function ping()
    {
        // TODO: Implement ping() method.
    }

    public function pong(PingPacket $pingData)
    {
        // TODO: Implement pong() method.
    }
}
