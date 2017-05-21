<?php

namespace Esockets;

use Esockets\base\AbstractAddress;
use Esockets\base\AbstractServer;
use Esockets\base\BlockingInterface;
use Esockets\base\BroadcastingInterface;
use Esockets\base\CallbackEvent;
use Esockets\base\CallbackEventsContainer;
use Esockets\base\Configurator;
use Esockets\base\HasClientsContainer;

class Server extends AbstractServer implements
    BroadcastingInterface,
    BlockingInterface
{
    private $server;
    private $eventFound;

    public function __construct(
        AbstractServer $server,
        Configurator $configurator
    )
    {
        $this->server = $server;
        $this->eventFound = new CallbackEventsContainer();

        $this->server->onFound(function ($connection, AbstractAddress $peerAddress = null) use ($configurator) {
            $peer = $configurator->makePeer($connection, $peerAddress);
            if ($this->server instanceof HasClientsContainer) {
                $this->server->getClientsContainer()->add($peer);
            }
            $this->eventFound->callEvents($peer);
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

    /**
     * @inheritDoc
     */
    public function getConnectionResource()
    {
        return $this->server->getConnectionResource();
    }

    public function disconnectAll()
    {
        if (!$this->server instanceof HasClientsContainer) {
            throw new \LogicException('Server does not have clients container.');
        }
        $this->server->getClientsContainer()->disconnectAll();
    }

    public function onDisconnectAll(callable $callback): CallbackEvent
    {
        if (!$this->server instanceof HasClientsContainer) {
            throw new \LogicException('Server does not have clients container.');
        }
        return $this->server->getClientsContainer()->onDisconnectAll($callback);
    }

    public function find()
    {
        $this->server->find();
    }

    public function onFound(callable $callback): CallbackEvent
    {
        return $this->eventFound->addEvent(CallbackEvent::create($callback));
    }

    public function sendToAll($data): bool
    {
        if (!$this->server instanceof HasClientsContainer) {
            throw new \LogicException('Server does not have clients container.');
        }
        return $this->server->getClientsContainer()->sendToAll($data);
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
}
