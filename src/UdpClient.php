<?php
/**
 * Net Client code snippet
 *
 * Created by PhpStorm.
 * User: yarullin
 * Date: 02.10.2015
 * Time: 8:55
 */

namespace maestroprog\esockets;


class UdpClient extends TcpClient
{
    protected function _connect()
    {
        if ($this->socket_domain === AF_UNIX) {
            throw new \Exception('Socket domain cannot be as AF_UNIX');
        }
        if ($this->connection = socket_create($this->socket_domain, SOCK_DGRAM, $this->socket_domain > 1 ? getprotobyname('udp') : 0)) {

            $this->setNonBlock(); // устанавливаем неблокирующий режим работы сокета

            parent::connect();
            return $this->connected = true;
        }
        // @TODO delete next line...
        trigger_error('Client connect failed', E_USER_ERROR);
        return false;
    }
}
