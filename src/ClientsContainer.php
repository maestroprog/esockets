<?php

namespace Esockets;

use Esockets\base\BroadcastingInterface;
use Esockets\base\CallbackEvent;
use Esockets\base\CallbackEventsContainer;
use Esockets\base\ClientsContainerInterface;

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
        $this->eventDisconnectAll = new CallbackEventsContainer();
    }

    public function add(Client $client)
    {
        $this->clients[$client->getPeerAddress()->__toString()] = $client;
        $client->onDisconnect(function () use ($client) {
            $this->remove($client);
        })->subscribe();
    }

    public function remove(Client $client)
    {
        $key = $client->getPeerAddress()->__toString();
        if (!isset($this->clients[$key])) {
            throw new \LogicException('Client already removed or not found.');
        }
        unset($this->clients[$key]);
        if (count($this->clients) === 0) {
            $this->eventDisconnectAll->callEvents();
        }
    }

    public function disconnectAll()
    {
        array_walk($this->clients, function (Client $client) {
            $client->disconnect();
        });
    }

    public function onDisconnectAll(callable $callback): CallbackEvent
    {
        return $this->eventDisconnectAll->addEvent(CallbackEvent::create($callback));
    }

    /**
     * @inheritDoc
     */
    public function read()
    {
        foreach ($this->clients as $client) {
            $client->read();
        }
    }

    /**
     * @inheritDoc
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
