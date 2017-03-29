<?php
/**
 * Created by PhpStorm.
 * User: yarullin
 * Date: 05.04.2016
 * Time: 8:35
 */

namespace Esockets\base;

interface ServerInterface extends ConnectorInterface, ConnectionsFinderInterface, BroadcastingInterface
{
    /**
     * Отключает всех пиров, оставляя входящее соединение открытым.
     *
     * @return void
     */
    public function disconnectAll();

    /**
     * Назначает обработчик события отсоединения всех пиров.
     * Обработчик вызывается при отсоединении последнего подключенного пира.
     *
     * @param callable $callback
     * @return void
     */
    public function onDisconnectAll(callable $callback);

    public function
}