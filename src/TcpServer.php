<?php
/**
 * Server Net code snippet
 *
 * Created by PhpStorm.
 * User: Руслан
 * Date: 01.10.2015
 * Time: 19:48
 */

namespace maestroprog\esockets;


use maestroprog\esockets\base\Net;
use maestroprog\esockets\base\ServerInterface;

class TcpServer extends Net implements ServerInterface
{
    /* server variables */

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

    /* event variables */

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
    private $event_accept;


    /* other variables */

    /**
     * for double use
     * @var bool
     */
    private $_open_try = false;

    /**
     * @see parent::connect(); Серверу не нужен ввод/вывод, поэтому он не будет вызывать родительский коннект.
     *
     * @return bool
     * @throws \Exception
     */
    public function connect()
    {
        if ($this->is_connected()) return true;

        if ($this->connection = socket_create($this->socket_domain, SOCK_STREAM, $this->socket_domain > 1 ? getprotobyname('tcp') : 0)) {
            socket_set_option($this->connection, SOL_SOCKET, SO_REUSEADDR, 1);
            if (socket_bind($this->connection, $this->socket_address, $this->socket_port)) {
                if (socket_listen($this->connection)) {
                    socket_set_nonblock($this->connection);
                    $this->_open_try = false; // сбрасываем флаг попытки открыть сервер
                    return $this->opened = true;
                } else {
                    throw new \Exception(socket_strerror(socket_last_error($this->connection)));
                }
            } else {
                $error = socket_last_error($this->connection);
                socket_clear_error($this->connection);
                \maestroprog\esockets\debug\Log::log('error: ' . $error);
                switch ($error) {
                    case SOCKET_EADDRINUSE:
                        // если сокет уже открыт - пробуем его закрыть и снова открыть
                        // @TODO socket close self::socket_close();
                        // @todo recheck this code
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
        }
        // @TODO delete next line...
        trigger_error('Server open failed', E_USER_ERROR);
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
        if ($this->socket_domain === AF_UNIX) {
            if (file_exists($this->socket_address))
                unlink($this->socket_address);
            else
                trigger_error(sprintf('Pipe file "%s" not found', $this->socket_address));
        }
    }

    /**
     * close server
     */
    public function disconnectAll()
    {

        foreach ($this->connections as $index => $peer) {
            /**
             * @var $peer \maestroprog\esockets\Peer
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

    public function read()
    {
        foreach ($this->connections as $dsc => $peer) {
            /**
             * @var $peer Peer
             */
            \maestroprog\esockets\debug\Log::log('Читаю пира ' . $peer->getDsc() . ' on adddress ' . $peer->getAddress());
            $peer->read();
        }
    }

    public function send($data)
    {
        $ok = true;
        foreach ($this->connections as $peer) {
            /**
             * @var $peer \maestroprog\esockets\Peer
             */
            $ok &= $peer->send($data);
        }
        return $ok;
    }

    public function ping()
    {
        \maestroprog\esockets\debug\Log::log('Пингуем пиров');
        foreach ($this->connections as $peer) {
            /**
             * @var $peer \maestroprog\esockets\Peer
             */
            $peer->ping();
        }
    }

    public function select()
    {
        $sockets = [$this->connection];
        foreach ($this->connections as $peer) {
            // todo а нужно ли это?
            $sockets[] = $peer->getConnection();
        }
        $write = [];
        $except = [];
        var_dump($sockets);
        if (false === ($rc = socket_select($sockets, $write, $except, null))) {
            \maestroprog\esockets\debug\Log::log('socket_select failed!');
        }
        var_dump($rc, $sockets, $write, $except);
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
                \maestroprog\esockets\debug\Log::log('Отсоединился пир ' . $peer->getDsc());
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
    }

    public function onDisconnectAll(callable $callback)
    {
        // TODO: Implement onDisconnectAll() method.
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
        $addr = $this->socket_address;
        $port = $this->socket_port;
    }
}
