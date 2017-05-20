<?php

namespace Esockets\base;

interface ConnectionsFinderInterface
{
    /**
     * Find the new connections.
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
     * $connectionFinder->onFound(function(resource $socket){
     *      return true; // every socket accept
     * });
     */
    public function onFound(callable $callback): CallbackEvent;
}
