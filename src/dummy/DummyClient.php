<?php

namespace Esockets\dummy;

use Esockets\base\AbstractAddress;
use Esockets\base\AbstractClient;
use Esockets\base\AbstractConnectionResource;
use Esockets\base\CallbackEventListener;
use Esockets\base\IoAwareInterface;

/**
 * Класс-заглушка ввода-вывода.
 * Ничего не делает.
 */
final class Dummy extends AbstractClient implements IoAwareInterface
{
    private $serverAddress;

    public function getReadBufferSize(): int
    {
        return 1;
    }

    public function getMaxPacketSizeForWriting(): int
    {
        return 0;
    }

    public function getPeerAddress(): AbstractAddress
    {
        return $this->serverAddress;
    }

    public function getClientAddress(): AbstractAddress
    {
        return new DummyAddress();
    }

    public function getConnectionResource(): AbstractConnectionResource
    {
        return new DummyConnectionResource();
    }

    public function connect(AbstractAddress $address)
    {
        $this->serverAddress = $address;
    }

    public function onConnect(callable $callback): CallbackEventListener
    {
        return CallbackEventListener::create(0, $callback, function () {
        });
    }

    public function reconnect(): bool
    {
        return true;
    }

    public function isConnected(): bool
    {
        return true;
    }

    public function disconnect()
    {
        ;
    }

    public function onDisconnect(callable $callback): CallbackEventListener
    {
        return CallbackEventListener::create(0, $callback, function () {
        });
    }

    public function read(int $length, bool $force)
    {
        ;
    }

    public function getReceivedBytesCount(): int
    {
        return 0;
    }

    public function getReceivedPacketCount(): int
    {
        return 0;
    }

    public function send($data): bool
    {
        return true;
    }

    public function getTransmittedBytesCount(): int
    {
        return 0;
    }

    public function getTransmittedPacketCount(): int
    {
        return 0;
    }
}
