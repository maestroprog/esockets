<?php

namespace Esockets\base;

/**
 * Интерфейс поиска новых или изменившихся соединений.
 */
interface ConnectionsFinderInterface
{
    /**
     * Ищет новые или изменившиеся соединения.
     *
     * @return void
     */
    public function find();

    /**
     * todo doc
     *
     * @param callable $callback
     * @return CallbackEvent
     *
     * @example
     * $connectionFinder->onFound(function($socket){
     *      return true; // every socket accept
     * });
     */
    public function onFound(callable $callback): CallbackEvent;
}
