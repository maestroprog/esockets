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
     * @param mixed $connection
     * @return void
     */
    public function accept($connection);

    /**
     * todo doc
     *
     * @param callable $callback
     * @return void
     *
     * @example
     * $connectionFinder->onFound(function(resource $socket){
     *      return true; // every socket accept
     * });
     */
    public function onFound(callable $callback);


    public function onAccept(callable $callback);
}
