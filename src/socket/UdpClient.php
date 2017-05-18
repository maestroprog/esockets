<?php

namespace Esockets\socket;

use Esockets\base\AbstractClient;
use Esockets\socket\Net;

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

    public function read(int $length, bool $need = false)
    {
        $buffer = null;
        $ip = null;
        $port = 0;
        if ($bytes = socket_recvfrom($this->socket, $buffer, $length, 0, $ip, $port)) {
            return [$ip, $port, $buffer];
        } elseif ($bytes === 0) {
            Log::log('0 bytes read');
        } else {
            Log::log('reading fail with error ' . socket_last_error($this->socket));
        }
        return false;
    }

    public function send(string &$data)
    {
        list($addr, $port) = $this->connection->getPeerAddress();
        socket_sendto($this->socket, $data, strlen($data), 0, $addr, $port);
    }
}
