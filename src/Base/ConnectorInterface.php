<?php

namespace Esockets\Base;

use Esockets\Base\Exception\ConnectionException;

/**
 * Интерфейс поддержки соединений (реальных или виртуальных).
 */
interface ConnectorInterface
{
    /**
     * Выполняет подключение к указанному адресу.
     *
     * @param AbstractAddress $address
     *
     * @return void
     * @throws ConnectionException
     */
    public function connect(AbstractAddress $address): void;

    /**
     * Назначает обработчик события возникающего при успешном подключении.
     *
     * @param callable $callback
     *
     * @return CallbackEventListener
     */
    public function onConnect(callable $callback): CallbackEventListener;

    /**
     * Выполняет переподключение.
     * При переподключении сначала вызывается @see ConnectorInterface::disconnect(),
     * а затем @see ConnectorInterface::connect().
     *
     * @return bool
     */
    public function reconnect(): bool;

    /**
     * Вернёт true, если находится в подключенном состоянии.
     *
     * @return bool
     */
    public function isConnected(): bool;

    /**
     * Выполняет отключение.
     *
     * @return void
     */
    public function disconnect(): void;

    /**
     * Назначает обработчик события возникающего при отключении.
     * Отключение может произойти не только при вызове @see ConnectorInterface::disconnect(),
     * но и при чтении, или записи, и при других операцих.
     * Поведение отключения всецело определяется разработчиком соединения.
     *
     * @param callable $callback
     *
     * @return CallbackEventListener
     */
    public function onDisconnect(callable $callback): CallbackEventListener;

    /**
     * Должен вернуть обёртку ресурса соединения.
     *
     * @return AbstractConnectionResource
     */
    public function getConnectionResource(): AbstractConnectionResource;
}
