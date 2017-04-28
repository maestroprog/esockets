<?php

namespace Esockets\net;


use Esockets\base\AbstractClient;
use Esockets\net\Net;

class UdpClient extends AbstractClient
{
    protected function _connect()
    {
        if ($this->socketDomain === AF_UNIX) {
            throw new \Exception('Socket domain cannot be as AF_UNIX');
        }
        $protocol = $this->socketDomain > 1 ? getprotobyname('udp') : 0;
        if ($this->connection = socket_create($this->socketDomain, SOCK_DGRAM, $protocol)) {

            $this->setNonBlock(); // устанавливаем неблокирующий режим работы сокета

            parent::connect();
            return $this->connected = true;
        }
        return false;
    }
}
