<?php

namespace Esockets;

use Esockets\base\AbstractAddress;
use Esockets\base\AbstractClient;
use Esockets\base\AbstractProtocol;
use Esockets\base\AbstractServer;
use Esockets\base\BlockingInterface;
use Esockets\base\BroadcastingInterface;
use Esockets\base\CallbackEvent;
use Esockets\base\Configurator;
use Esockets\base\ConnectionsFinderInterface;
use Esockets\base\ConnectionWrapperInterface;
use Esockets\base\ConnectorInterface;
use Esockets\base\exception\ReadException;

class Server implements ConnectorInterface, ConnectionsFinderInterface, BroadcastingInterface, BlockingInterface
{
    private $server;
    private $clientsContainer;
    private $eventFound;

    public function __construct(AbstractServer $server, Configurator $configurator)
    {
        $this->server = $server;
        $this->clientsContainer = new ClientsContainer();
        $this->server->onFound(function ($connection) use ($configurator) {
            $peer = $configurator->makePeer($connection);
            if (is_callable($this->eventFound)) {
                call_user_func($this->eventFound, $peer);
            }
        });
    }

    public function connect(AbstractAddress $address)
    {
        $this->server->connect($address);
    }

    public function onConnect(callable $callback): CallbackEvent
    {
        return $this->server->onConnect($callback);
    }

    public function reconnect(): bool
    {
        return $this->server->reconnect();
    }

    public function isConnected(): bool
    {
        return $this->server->isConnected();
    }

    public function disconnect()
    {
        $this->server->disconnect();
    }

    public function onDisconnect(callable $callback): CallbackEvent
    {
        return $this->server->onDisconnect($callback);
    }

    public function disconnectAll()
    {
        $this->clientsContainer->disconnectAll();
    }

    public function onDisconnectAll(callable $callback)
    {
        $this->clientsContainer->onDisconnectAll($callback);
    }

    public function find()
    {
        $this->server->find();
    }

    public function onFound(callable $callback): CallbackEvent
    {
        return $this->server->onFound($callback);
    }

    public function accept($connection)
    {

    }

    /**
     * @inheritDoc
     */
    public function read()
    {
        $this->clientsContainer->read();
    }

    /**
     * @inheritDoc
     */
    public function sendToAll($data): bool
    {
        return $this->clientsContainer->sendToAll($data);
    }

    public function block()
    {
        if ($this->server instanceof BlockingInterface) {
            $this->server->block();
        }
    }

    public function unblock()
    {
        if ($this->server instanceof BlockingInterface) {
            $this->server->unblock();
        }
    }

    /**
     *
     * protected function _onConnectPeer(&$connection)
     * {
     * while (isset($this->connections[$this->connections_dsc])) {
     * $this->connections_dsc++;
     * }
     * if ($peer = new Peer($connection, $this->connections_dsc)) {
     * $peer->setNonBlock();
     * $this->connections[$this->connections_dsc] = &$peer;
     * if (is_callable($this->event_accept)) {
     * call_user_func_array($this->event_accept, [$peer]);
     * }
     * $peer->onDisconnect(function () use ($peer) {
     * unset($this->connections[$peer->getDsc()]);
     * $this->_onDisconnectPeer($peer);
     * });
     * return true;
     * } else {
     * trigger_error('Peer connection error');
     * return false;
     * }
     * }
     */
}
