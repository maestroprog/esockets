<?php

namespace Esockets\base;


abstract class AbstractServer implements
    ConnectorInterface,
    ConnectionSupportInterface,
    ConnectionsFinderInterface,
    BroadcastingInterface
{
    /**
     * Отключает всех пиров, оставляя входящее соединение открытым.
     *
     * @return void
     */
    abstract public function disconnectAll();

    /**
     * Назначает обработчик события отсоединения всех пиров.
     * Обработчик вызывается при отсоединении последнего подключенного пира.
     *
     * @param callable $callback
     * @return void
     */
    abstract public function onDisconnectAll(callable $callback);
}