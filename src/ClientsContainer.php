<?php

namespace Esockets;

use Esockets\Base\AbstractAddress;
use Esockets\Base\BroadcastingInterface;
use Esockets\Base\CallbackEventListener;
use Esockets\Base\ClientsContainerInterface;
use Esockets\Base\Event;

/**
 * Серверный контейнер клиентских соединений.
 * Обеспечивает работу отправки сообщений broadcast-ом,
 * и управление всеми клиентскими соединениями.
 */
class ClientsContainer implements ClientsContainerInterface, BroadcastingInterface
{
    /**
     * @var Client[]
     */
    private $clients = [];

    private $eventDisconnectAll;

    public function __construct()
    {
        $this->eventDisconnectAll = new Event();
    }

    /**
     * @inheritdoc
     */
    public function add(Client $client): void
    {
        $this->clients[$client->getPeerAddress()->__toString()] = $client;
        $client->onDisconnect(function () use ($client) {
            $this->remove($client);
        });
    }

    /**
     * @inheritdoc
     */
    public function remove(Client $client): void
    {
        $key = $client->getPeerAddress()->__toString();
        if (!isset($this->clients[$key])) {
            throw new \LogicException('Client already removed or not found.');
        }
        unset($this->clients[$key]);
        if (count($this->clients) === 0) {
            $this->eventDisconnectAll->call();
        }
    }

    /**
     * @inheritdoc
     */
    public function list(): array
    {
        return $this->clients;
    }

    /**
     * @inheritdoc
     */
    public function exists(Client $client): bool
    {
        return $this->existsByAddress($client->getClientAddress());
    }

    /**
     * @inheritdoc
     */
    public function existsByAddress(AbstractAddress $address): bool
    {
        return array_key_exists($address->__toString(), $this->clients);
    }

    /**
     * @inheritdoc
     */
    public function getByAddress(AbstractAddress $address): Client
    {
        if (!$this->existsByAddress($address)) {
            throw new \RuntimeException('Client not found.');
        }
        return $this->clients[$address->__toString()];
    }

    /**
     * @inheritdoc
     */
    public function disconnectAll(): void
    {
        array_walk($this->clients, function (Client $client) {
            $client->disconnect();
        });
    }

    /**
     * @inheritdoc
     */
    public function onDisconnectAll(callable $callback): CallbackEventListener
    {
        return $this->eventDisconnectAll->attachCallbackListener($callback);
    }

    /**
     * @inheritdoc
     */
    public function sendToAll($data): bool
    {
        $result = false;
        foreach ($this->clients as $client) {
            $result = $client->send($data) or $result;
        }
        return $result;
    }
}
