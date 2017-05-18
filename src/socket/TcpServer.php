<?php

namespace Esockets\socket;

use Esockets\base\AbstractAddress;
use Esockets\base\AbstractServer;
use Esockets\base\exception\ConnectionException;

final class TcpServer extends AbstractServer
{
    protected $socket;
    protected $connected = false;

    protected $errorHandler;

    /**
     * @var Peer[]
     */
    protected $connections = [];

    /**
     * @var int
     */
    protected $connections_dsc = 0;

    /**
     * @var bool server state
     */
    private $opened = false;

    /**
     * @var callable
     */
    private $event_disconnect;

    /**
     * @var callable
     */
    private $event_disconnect_peer;

    /**
     * @var callable
     */
    private $event_disconnect_all;

    /**
     * @var callable
     */
    private $event_accept;

    /**
     * for reconnect
     * @var bool
     */
    private $_open_try = false;

    /**
     * @inheritDoc
     */
    public function __construct(int $socketDomain, SocketErrorHandler $errorHandler)
    {
        $this->errorHandler = $errorHandler;
        if (!($this->socket = socket_create($socketDomain, SOCK_STREAM, SOL_TCP))) {
            throw new ConnectionException(socket_strerror(socket_last_error()));
        } else {
            $this->errorHandler->setSocket($this->socket);
        }

        socket_set_option($this->socket, SOL_SOCKET, SO_REUSEADDR, 1);
    }


    public function connect(AbstractAddress $listenAddress)
    {
        if ($this->connected) {
            throw new \LogicException('Socket is already connected.');
        }
        if ($this->isConnected() && $listenAddress instanceof Ipv4Address) {
            if (socket_connect($this->socket, $listenAddress->getIp(), $listenAddress->getPort())) {
                $this->connected = true;
            }
        } elseif ($this->isUnixAddress() && $listenAddress instanceof UnixAddress) {
            if (socket_connect($this->socket, $listenAddress->getSockPath())) {
                $this->connected = true;
            }
        } else {
            throw new \LogicException('Unknown socket address.');
        }

        if (!$this->connected) {
            $this->errorHandler->handleError();
        }

        if (socket_bind($this->socket, $listenAddress->getIp(), $listenAddress->getPort())) {
            if (socket_listen($this->socket)) {
                $this->unblock();
                $this->_open_try = false; // сбрасываем флаг попытки открыть сервер
                return $this->opened = true;
            } else {
                throw new ConnectionException(socket_strerror(socket_last_error($this->socket)));
            }
        } else {
            $error = socket_last_error($this->connection);
            socket_clear_error($this->connection);
            switch ($error) {
                case SOCKET_EADDRINUSE:
                    // если сокет уже открыт - пробуем его закрыть и снова открыть
                    // closing socket and try restart
                    $this->disconnect();
                    if (!$this->_open_try) {
                        $this->_open_try = true;
                        return $this->connect();
                    }
                    break;
                default:
                    throw new \Exception(socket_strerror($error));
            }
        }

        return false;
    }

    public function is_connected()
    {
        return $this->opened;
    }

    public function disconnect()
    {
        $this->disconnectAll();
        if ($this->opened)
            parent::disconnect();
        $this->opened = false;
        if ($this->socketDomain === AF_UNIX) {
            if (file_exists($this->socketAddress)) {
                unlink($this->socketAddress);
            } else {
                trigger_error(sprintf('Pipe file "%s" not found', $this->socketAddress));
            }
        }
    }

    public function disconnectAll()
    {

        foreach ($this->connections as $index => $peer) {
            /**
             * @var $peer \Esockets\Peer
             */
            $peer->disconnect();
            unset($this->connections[$index], $peer);
        }
    }

    public function listen()
    {
        if ($connection = socket_accept($this->connection)) {
            return $this->_onConnectPeer($connection);
        }
        return false;
    }

    public function read(bool $need = false)
    {
        foreach ($this->connections as $dsc => $peer) {
            /**
             * @var $peer Peer
             */
            $peer->read();
        }
    }

    public function send($data)
    {
        $ok = true;
        foreach ($this->connections as $peer) {
            /**
             * @var $peer \Esockets\Peer
             */
            $ok &= $peer->send($data);
        }
        return $ok;
    }

    public function ping()
    {
        foreach ($this->connections as $peer) {
            /**
             * @var $peer \Esockets\Peer
             */
            $peer->ping();
        }
    }

    /**
     * @return bool
     */
    public function live()
    {
        $this->read();
        if ($this->is_connected()) {
            $this->setTime();
            $this->listen();
            $this->read();
            if (($this->getTime(self::LIVE_LAST_PING) + self::SOCKET_TIMEOUT * 2) <= time()) {
                // иногда пингуем все соединения
                $this->ping();
            }
        } elseif ($this->socketReconnect && $this->getTime() + self::SOCKET_TIMEOUT > time()) {
            if ($this->getTime(self::LIVE_LAST_RECONNECT) + self::SOCKET_RECONNECT <= time()) {
                if ($this->connect()) {
                    $this->setTime();
                }
            } else {
                $this->setTime(self::LIVE_LAST_RECONNECT);
            }
        } else {
            return false;
        }
        return true;
    }

    public function select()
    {
        $sockets = [$this->connection];
        foreach ($this->connections as $peer) {
            $sockets[] = $peer->getConnection();
        }
        $write = [];
        $except = [];
        if (false === ($rc = socket_select($sockets, $write, $except, null))) {
            throw new \Exception('socket_select failed!');
        }
    }

    /**
     * @param callable $callback
     */
    public function onDisconnect(callable $callback)
    {
        $this->event_disconnect = $callback;
    }

    protected function _onDisconnect()
    {
        if (is_callable($this->event_disconnect)) {
            call_user_func($this->event_disconnect);
        }
    }

    /**
     * @param callable $callback ($peer)
     * Give callback function($client)
     */
    public function onConnectPeer(callable $callback)
    {
        $this->event_accept = $callback;
    }

    protected function _onConnectPeer(&$connection)
    {
        while (isset($this->connections[$this->connections_dsc])) {
            $this->connections_dsc++;
        }
        if ($peer = new Peer($connection, $this->connections_dsc)) {
            $peer->setNonBlock();
            $this->connections[$this->connections_dsc] = &$peer;
            if (is_callable($this->event_accept)) {
                call_user_func_array($this->event_accept, [$peer]);
            }
            $peer->onDisconnect(function () use ($peer) {
                unset($this->connections[$peer->getDsc()]);
                $this->_onDisconnectPeer($peer);
            });
            return true;
        } else {
            trigger_error('Peer connection error');
            return false;
        }
    }

    public function onDisconnectPeer(callable $callback)
    {
        $this->event_disconnect_peer = $callback;
    }

    public function _onDisconnectPeer(Peer $peer)
    {
        if (is_callable($this->event_disconnect_peer)) {
            call_user_func_array($this->event_disconnect_peer, [$peer]);
        }
        if (count($this->connections) === 0) {
            $this->_onDisconnectAll();
        }
    }

    public function onDisconnectAll(callable $callback)
    {
        $this->event_disconnect_all = $callback;
    }

    public function _onDisconnectAll()
    {
        if (is_callable($this->event_disconnect_all)) {
            call_user_func($this->event_disconnect_all);
        }
    }

    public function getConnectedCount()
    {
        return count($this->connections);
    }

    public function getPeerByDsc(int $dsc)
    {
        if (!isset($this->connections[$dsc]) || !$this->connections[$dsc] instanceof Peer) {
            throw new \Exception('Cannot get Peer by this "dsc" ' . $dsc);
        }
        return $this->connections[$dsc];
    }

    protected function getPeerName(string &$addr, int &$port)
    {
        $addr = $this->socketAddress;
        $port = $this->socketPort;
    }
}
