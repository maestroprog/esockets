<?php

namespace Esockets\dummy;

use Esockets\base\AbstractAddress;
use Esockets\base\AbstractClient;
use Esockets\base\CallbackEvent;
use Esockets\base\IoAwareInterface;

/**
 * Класс-заглушка ввода-вывода.
 */
final class Dummy extends AbstractClient implements IoAwareInterface
{
    private $serverAddress;

    public function getPeerAddress(): AbstractAddress
    {
        return $this->serverAddress;
    }

    public function getClientAddress(): AbstractAddress
    {
        return new DummyAddress();
    }

    public function getConnectionResource():AbstractConnectionResource
    {
        return null;
    }

    public function connect(AbstractAddress $address)
    {
        $this->serverAddress = $address;
    }

    public function onConnect(callable $callback): CallbackEvent
    {
        return CallbackEvent::create($callback);
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

    public function onDisconnect(callable $callback): CallbackEvent
    {
        return CallbackEvent::create($callback);
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
