<?php
/**
 * Created by PhpStorm.
 * User: yarullin
 * Date: 05.04.2016
 * Time: 8:35
 */

namespace Esockets\base;

interface ServerInterface extends NetInterface
{

    /**
     * Слушает входящие соединения.
     * Вызывает обработчик события, заданный в onConnectPeer().
     */
    public function listen();

    /**
     * Назначает событие соединения клиента.
     *
     * @param callable $callback
     */
    public function onConnectPeer(callable $callback);

    /**
     * Назначает событие при отсоединении пира.
     *
     * @param callable $callback
     */
    public function onDisconnectPeer(callable $callback);

    /**
     * Отключает всех пиров, оставляя входящее соединение открытым.
     */
    public function disconnectAll();

    /**
     * Назначает событие при отсоединении всех пиров.
     * Событие вызывается при отсоединении последнего подключенного пира.
     *
     * @param callable $callback
     */
    public function onDisconnectAll(callable $callback);
}
