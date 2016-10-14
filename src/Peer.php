<?php
/**
 * Net Client code snippet
 *
 * Created by PhpStorm.
 * User: yarullin
 * Date: 02.10.2015
 * Time: 8:55
 */

namespace Esockets;


class Peer extends Net
{
    /**
     * @var bool connection state
     */
    private $connected = false;

    /* event variables */

    /**
     * @var array of callable
     */
    private $event_disconnect = [];

    /**
     * @var int дескриптор пира
     */
    private $dsc;


    public function __construct(&$connection, $dsc)
    {
        if (is_resource($connection)) {
            $this->connection = $connection;
            $this->connected = true;
            $this->dsc = $dsc;
            parent::__construct();
            return $this;
        }
        return false;
    }

    public function connect()
    {
        // метод-заглушка
        // TODO: Implement connect() method.
    }

    public function is_connected()
    {
        return $this->connected;
        // TODO: Implement is_connected() method.
    }


    public function disconnect()
    {
        parent::disconnect();
        $this->connected = false;
    }

    public function onDisconnect(callable $callback)
    {
        $this->event_disconnect[] = $callback;
        return max(array_keys($this->event_disconnect));
    }

    protected function _onDisconnect()
    {
        foreach ($this->event_disconnect as $event) {
            call_user_func($event);
        }
    }

    public function getAddress()
    {
        $address = $port = null;
        if (socket_getpeername($this->connection, $address, $port)) {
            return $address . ':' . $port;
        } else {
            return 'Unknown';
        }
    }

    public function getDsc()
    {
        return $this->dsc;
    }

    public function getConnection()
    {
        return $this->connection;
    }
}
