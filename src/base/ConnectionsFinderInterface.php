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
     * @param resource $socket
     * @return void
     */
    public function accept(resource $socket);

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