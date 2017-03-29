<?php
/**
 * Created by PhpStorm.
 * User: maestroprog
 * Date: 22.03.2017
 * Time: 19:45
 */

namespace Esockets\base;

// todo смотри в тетрадку
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