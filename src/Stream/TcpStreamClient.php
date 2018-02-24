<?php

namespace Esockets\Stream;

use Esockets\Base\AbstractAddress;
use Esockets\Base\AbstractClient;
use Esockets\Base\AbstractConnectionResource;
use Esockets\Base\BlockingInterface;
use Esockets\Base\CallbackEventListener;

class TcpStreamClient extends AbstractClient implements BlockingInterface
{
    /**
     * @inheritDoc
     */
    public function __construct()
    {
        stream_socket_client();
    }

    /**
     * @inheritDoc
     */
    public function getPeerAddress(): AbstractAddress
    {
        // TODO: Implement getPeerAddress() method.
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
    public function connect(AbstractAddress $address): void
    {
        // TODO: Implement connect() method.
    }

    /**
     * @inheritDoc
     */
    public function onConnect(callable $callback): CallbackEventListener
    {
        // TODO: Implement onConnect() method.
    }

    /**
     * @inheritDoc
     */
    public function reconnect(): bool
    {
        // TODO: Implement reconnect() method.
    }

    /**
     * @inheritDoc
     */
    public function isConnected(): bool
    {
        // TODO: Implement isConnected() method.
    }

    /**
     * @inheritDoc
     */
    public function disconnect(): void
    {
        // TODO: Implement disconnect() method.
    }

    /**
     * @inheritDoc
     */
    public function onDisconnect(callable $callback): CallbackEventListener
    {
        // TODO: Implement onDisconnect() method.
    }

    /**
     * @inheritDoc
     */
    public function getConnectionResource(): AbstractConnectionResource
    {
        // TODO: Implement getConnectionResource() method.
    }

    public function ready(): bool
    {
        // TODO: Implement ready() method.
    }

    /**
     * @inheritDoc
     */
    public function getReadBufferSize(): int
    {
        // TODO: Implement getReadBufferSize() method.
    }

    /**
     * @inheritDoc
     */
    public function getMaxPacketSizeForWriting(): int
    {
        // TODO: Implement getMaxPacketSizeForWriting() method.
    }

    /**
     * @inheritDoc
     */
    public function read(int $length, bool $force)
    {
        // TODO: Implement read() method.
    }

    /**
     * @inheritDoc
     */
    public function send($data): bool
    {
        // TODO: Implement send() method.
    }

    /**
     * @inheritDoc
     */
    public function block(): void
    {
        // TODO: Implement block() method.
    }

    /**
     * @inheritDoc
     */
    public function unblock(): void
    {
        // TODO: Implement unblock() method.
    }

    /**
     * @inheritdoc
     */
    public function isBlocked(): bool
    {
        // TODO: Implement isBlocked() method.
    }

    /**
     * @inheritDoc
     */
    public function getReceivedBytesCount(): int
    {
        // TODO: Implement getReceivedBytesCount() method.
    }

    /**
     * @inheritDoc
     */
    public function getReceivedPacketCount(): int
    {
        // TODO: Implement getReceivedPacketCount() method.
    }

    /**
     * @inheritDoc
     */
    public function getTransmittedBytesCount(): int
    {
        // TODO: Implement getTransmittedBytesCount() method.
    }

    /**
     * @inheritDoc
     */
    public function getTransmittedPacketCount(): int
    {
        // TODO: Implement getTransmittedPacketCount() method.
    }

}
