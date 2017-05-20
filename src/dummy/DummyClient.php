<?php

namespace Esockets\dummy;

use Esockets\base\AbstractAddress;
use Esockets\base\AbstractClient;
use Esockets\base\CallbackEvent;
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
    public function getServerAddress(): AbstractAddress
    {
        // TODO: Implement getServerAddress() method.
    }

    /**
     * @inheritDoc
     */
    public function getClientAddress(): AbstractAddress
    {
        // TODO: Implement getClientAddress() method.
    }

    /**
     * @inheritDoc
     */
    public function getConnectionResource()
    {
        // TODO: Implement getConnectionResource() method.
    }

    /**
     * @inheritDoc
     */
    public function connect(AbstractAddress $address)
    {
        // TODO: Implement connect() method.
    }

    public function onConnect(callable $callback): CallbackEvent
    {
        // TODO: Implement onConnect() method.
    }

    public function reconnect(): bool
    {
        // TODO: Implement reconnect() method.
    }

    public function isConnected(): bool
    {
        // TODO: Implement isConnected() method.
    }

    public function disconnect()
    {
        // TODO: Implement disconnect() method.
    }

    public function onDisconnect(callable $callback): CallbackEvent
    {
        // TODO: Implement onDisconnect() method.
    }

    /**
     * @inheritDoc
     */
    public function read(int $length, bool $force)
    {
        // TODO: Implement read() method.
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
}
