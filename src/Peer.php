<?php

namespace Esockets;

use Esockets\base\AbstractClient;
use Esockets\base\AbstractServer;
use Esockets\net\Net;

class Peer extends Client
{
    /**
     * @var bool connection state
     */
    private $connected = false;

    /**
     * @var int дескриптор пира
     */
    private $dsc;

    /**
     * @var array of callable
     */
    private $event_disconnect = [];


    /**
     * Peer constructor.
     * @param array $connection
     * @param $dsc
     * @throws \Exception
     */
    public function __construct(&$connection, $dsc)
    {
        if (!is_resource($connection) || 'Socket' !== ($t = get_resource_type($connection))) {
            throw new \Exception('Given connection not is resource of socket connection');
        }
        $this->connection = $connection;
        $this->connected = true;
        $this->dsc = $dsc;
        parent::__construct();
        parent::connect();
    }

    public function is_connected()
    {
        return $this->connected;
    }

    public function disconnect()
    {
        $this->connected = false; // как только начали дисконнектиться-ставим флаг
        parent::disconnect();
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

    protected function getPeerName(string &$addr, int &$port)
    {
        socket_getpeername($this->connection, $addr, $port);
    }

    public function getDsc()
    {
        return $this->dsc;
    }
}
