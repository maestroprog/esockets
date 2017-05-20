<?php

namespace Esockets\base;

interface ConnectorInterface
{
    /**
     * @param AbstractAddress $address
     * @return void
     */
    public function connect(AbstractAddress $address);

    public function onConnect(callable $callback): CallbackEvent;

    public function reconnect(): bool;

    public function isConnected(): bool;

    public function disconnect();

    public function onDisconnect(callable $callback): CallbackEvent;
}
