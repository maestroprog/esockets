<?php
/**
 * Server Net code snippet
 *
 * Created by PhpStorm.
 * User: Руслан
 * Date: 01.10.2015
 * Time: 19:48
 */

namespace Esockets;


class Server extends Net implements ServerInterface
{
    /* server variables */

    /**
     * @var array Peer
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

    public function open()
    {
        return $this->opened ?: $this->_open();
    }

    /**
     * close server
     */
    public function close()
    {

        foreach ($this->connections as $index => $peer) {
            /**
             * @var $peer \Esockets\Peer
             */
            $peer->doDisconnect();
            unset($this->connections[$index], $peer);
        }

        if ($this->socket_domain === AF_UNIX) {
            if (file_exists($this->socket_address))
                unlink($this->socket_address);
            else
                trigger_error(sprintf('Pipe file "%s" not found', $this->socket_address));
        }
        if ($this->opened)
            parent::close();
        $this->opened = false;
        // /@TODO recheck this code
    }

    public function ping()
    {
        error_log('Пингуем пиров');
        foreach ($this->connections as $peer) {
            /**
             * @var $peer \Esockets\Peer
             */
            $peer->ping();
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

    public function doDisconnect($client)
    {
        /**
         * TODO
         */
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

    public function doAccept()
    {
        if ($connection = socket_accept($this->connection)) {
            return $this->_onAccept($connection);
        }
        return false;
    }

    /**
     * @param callable $callback ($peer)
     * Give callback function($client)
     */
    public function onAccept(callable $callback)
    {
        $this->event_accept = $callback;
    }

    public function doReceive()
    {
        foreach ($this->connections as $dsc => $peer) {
            /**
             * @var $peer Peer
             */
            error_log('Читаю пира ' . $peer->getDsc() . ' on adddress ' . $dsc);
            $peer->doReceive();
        }
    }

    protected function _onAccept(&$connection)
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
                error_log('Отсоединился пир ' . $peer->getDsc());
                unset($this->connections[$peer->getDsc()]);
                $this->_onDisconnectPeer($peer);
            });
            return true;
        } else {
            trigger_error('Peer connection error');
            return false;
        }
    }

    private function _open()
    {
        if ($this->connection = socket_create($this->socket_domain, SOCK_STREAM, $this->socket_domain > 1 ? getprotobyname('tcp') : 0)) {
            socket_set_option($this->connection, SOL_SOCKET, SO_REUSEADDR, true);
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
                error_log('error: ' . $error);
                switch ($error) {
                    case SOCKET_EADDRINUSE:
                        // если сокет уже открыт - пробуем его закрыть и снова открыть
                        // @TODO socket close self::socket_close();
                        // @todo recheck this code
                        // closing socket and try restart
                        $this->close();
                        if (!$this->_open_try) {
                            $this->_open_try = true;
                            return $this->_open();
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
}
