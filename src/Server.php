<?php

namespace Esockets;

use Esockets\base\AbstractAddress;
use Esockets\base\AbstractClient;
use Esockets\base\AbstractProtocol;
use Esockets\base\AbstractServer;
use Esockets\base\ConnectionWrapperInterface;
use Esockets\base\ConnectorInterface;
use Esockets\base\exception\ReadException;

class Server extends AbstractServer implements ConnectionWrapperInterface
{
    private $server;

    public function __construct(ConnectorInterface $server, AbstractProtocol $protocol)
    {
        $this->server = $server;
    }

    public function connect(AbstractAddress $address)
    {
        // TODO: Implement connect() method.
    }

    public function onConnect(callable $callback)
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

    public function onDisconnect(callable $callback)
    {
        // TODO: Implement onDisconnect() method.
    }

    public function disconnectAll()
    {
        // TODO: Implement disconnectAll() method.
    }

    public function onDisconnectAll(callable $callback)
    {
        // TODO: Implement onDisconnectAll() method.
    }

    public function find()
    {
        // TODO: Implement find() method.
    }

    public function accept($connection)
    {
        // TODO: Implement accept() method.
    }

    public function onFound(callable $callback)
    {
        // TODO: Implement onFound() method.
    }

    public function onAccept(callable $callback)
    {
        // TODO: Implement onAccept() method.
    }

    public function block()
    {
        // TODO: Implement block() method.
    }

    public function unblock()
    {
        // TODO: Implement unblock() method.
    }

    public function read()
    {
        // TODO: Implement read() method.
    }

    public function returnRead()
    {
        // TODO: Implement returnRead() method.
    }

    public function onReceive(callable $callback)
    {
        // TODO: Implement onReceive() method.
    }

    public function send($data): bool
    {
        // TODO: Implement send() method.
    }

    public function sendToAll($data)
    {
        // TODO: Implement sendToAll() method.
    }
}
