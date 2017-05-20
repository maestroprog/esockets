<?php

namespace Esockets\base;

use Esockets\Client;

/**
 * Интерфейс контейнера клиентов.
 * В контейнер клиента можно добавить
 */
interface ClientsContainerInterface
{
    /**
     * Добавляет клиента в контейнер.
     * При удалении клиента из контейнера,
     *
     * @param $client Client
     */
    public function add(Client $client);

    /**
     * Удаляет клиента из контейнера.
     *
     * @param $client Client
     */
    public function remove(Client $client);

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
     * @return CallbackEvent
     */
    public function onDisconnectAll(callable $callback): CallbackEvent;
}
