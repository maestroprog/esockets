<?php

namespace Esockets;

use Esockets\Base\AbstractAddress;
use Esockets\Base\AbstractConnectionResource;
use Esockets\Base\AbstractServer;
use Esockets\Base\BlockingInterface;
use Esockets\Base\BroadcastingInterface;
use Esockets\Base\CallbackEventListener;
use Esockets\Base\Configurator;
use Esockets\Base\Event;
use Esockets\Base\HasClientsContainer;

/**
 * Обёртка над серверным соединением.
 * Использует конфигуратор (фабрику) для создания серверных клиентов при подключении оных.
 */
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
        $this->eventFound = new Event();

        $this->server->onFound(function ($connection) use ($configurator) {
            $peer = $configurator->makePeer($connection);
            if ($this->server instanceof HasClientsContainer) {
                $this->server->getClientsContainer()->add($peer);
            }
            $this->eventFound->call($peer);
        });
    }

    /**
     * @inheritdoc
     */
    public function connect(AbstractAddress $address): void
    {
        $this->server->connect($address);
    }

    /**
     * @inheritdoc
     */
    public function onConnect(callable $callback): CallbackEventListener
    {
        return $this->server->onConnect($callback);
    }

    /**
     * @inheritdoc
     */
    public function reconnect(): bool
    {
        return $this->server->reconnect();
    }

    /**
     * @inheritdoc
     */
    public function isConnected(): bool
    {
        return $this->server->isConnected();
    }

    /**
     * @inheritdoc
     */
    public function disconnect(): void
    {
        $this->server->disconnect();
    }

    /**
     * @inheritdoc
     */
    public function onDisconnect(callable $callback): CallbackEventListener
    {
        return $this->server->onDisconnect($callback);
    }

    /**
     * @inheritdoc
     */
    public function getConnectionResource(): AbstractConnectionResource
    {
        return $this->server->getConnectionResource();
    }

    /**
     * @inheritdoc
     */
    public function disconnectAll(): void
    {
        if (!$this->server instanceof HasClientsContainer) {
            throw new \LogicException('Server does not have clients container.');
        }
        $clientsContainer = $this->server->getClientsContainer();
        if (!$clientsContainer instanceof BroadcastingInterface) {
            throw new \LogicException('Clients container does not support disconnectAll().');
        }
        $clientsContainer->disconnectAll();
    }

    /**
     * @inheritdoc
     */
    public function onDisconnectAll(callable $callback): CallbackEventListener
    {
        if (!$this->server instanceof HasClientsContainer) {
            throw new \LogicException('Server does not have clients container.');
        }
        $clientsContainer = $this->server->getClientsContainer();
        if (!$clientsContainer instanceof BroadcastingInterface) {
            throw new \LogicException('Clients container does not support onDisconnectAll().');
        }
        return $clientsContainer->onDisconnectAll($callback);
    }

    /**
     * @inheritdoc
     */
    public function find(): void
    {
        $this->server->find();
    }

    /**
     * @inheritdoc
     */
    public function onFound(callable $callback): CallbackEventListener
    {
        return $this->eventFound->attachCallbackListener($callback);
    }

    /**
     * @inheritdoc
     */
    public function sendToAll($data): bool
    {
        if (!$this->server instanceof HasClientsContainer) {
            throw new \LogicException('Server does not have clients container.');
        }
        $clientsContainer = $this->server->getClientsContainer();
        if (!$clientsContainer instanceof BroadcastingInterface) {
            throw new \LogicException('Clients container does not support sendToAll().');
        }
        return $clientsContainer->sendToAll($data);
    }

    /**
     * @inheritdoc
     */
    public function block(): void
    {
        if ($this->server instanceof BlockingInterface) {
            $this->server->block();
        }
    }

    /**
     * @inheritdoc
     */
    public function unblock(): void
    {
        if ($this->server instanceof BlockingInterface) {
            $this->server->unblock();
        }
    }

    /**
     * @inheritdoc
     */
    public function isBlocked(): bool
    {
        if ($this->server instanceof BlockingInterface) {
            return $this->server->isBlocked();
        }

        return false;
    }
}
