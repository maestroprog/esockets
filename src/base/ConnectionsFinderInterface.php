<?php

namespace Esockets\base;

/**
 * Интерфейс поиска новых или изменившихся соединений.
 */
interface ConnectionsFinderInterface
{
    /**
     * Ищет новые или изменившиеся соединения.
     * Со всех изменивших состояние соединений будет выполнено чтение.
     *
     * @return void
     */
    public function find();

    /**
     * Назначает обработчик для события возникающего при нахождении нового подключения.
     * При нахождении нового подключения будет вызван указанный callback.
     *
     * @param callable $callback
     * @return CallbackEventListener
     *
     * @example $connectionFinder->onFound(function(Client $client){
     *      $client->send('Hello'); // отправим приветствие
     * });
     */
    public function onFound(callable $callback): CallbackEventListener;
}
