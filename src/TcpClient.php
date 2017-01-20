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

use Esockets\base\Net;

class TcpClient extends Net
{
    /**
     * @var bool connection state
     */
    protected $connected = false;

    /**
     * @var callable
     */
    protected $event_disconnect;

    public function connect()
    {
        return $this->is_connected() ?: $this->_connect();
    }

    public function is_connected()
    {
        return $this->connected;
    }

    public function disconnect()
    {
        $this->connected = false; // как только начали дисконнектиться - ставим флаг
        parent::disconnect();
    }

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

    protected function _connect()
    {
        $protocol = $this->socket_domain > 1 ? getprotobyname('tcp') : 0;
        if ($this->connection = socket_create($this->socket_domain, SOCK_STREAM, $protocol)) {
            if (socket_connect($this->connection, $this->socket_address, $this->socket_port)) {

                parent::connect();
                $this->setNonBlock(); // устанавливаем неблокирующий режим работы сокета

                return $this->connected = true;
            } else {
                $error = socket_last_error($this->connection);
                socket_clear_error($this->connection);
                switch ($error) {
                    case SOCKET_ECONNREFUSED:
                    case SOCKET_ENOENT:
                        // если отсутствует файл сокета,
                        // либо соединиться со слушающим сокетом не удалось - возвращаем false
                        $this->disconnect();
                        return false;
                    default:
                        // в иных случаях кидаем исключение
                        throw new \Exception(socket_strerror($error));

                }
            }
        }
        return false;
    }

    protected function getPeerName(string &$addr, int &$port)
    {
        $addr = $this->socket_address;
        $port = $this->socket_port;
    }
}
