<?php

namespace Esockets\base;


interface ConnectorInterface
{
    /**
     * @param string $ip
     * @param int $port
     * @return void
     */
    public function connect(string $ip, int $port);

    public function onConnect(callable $callback);

    public function isConnected(): bool;

    public function disconnect();

    public function onDisconnect(callable $callback);
}