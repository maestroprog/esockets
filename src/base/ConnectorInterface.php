<?php
/**
 * Created by PhpStorm.
 * User: maestroprog
 * Date: 22.03.2017
 * Time: 19:41
 */

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

    public function disconnect();

    public function onDisconnect(callable $callback);
}