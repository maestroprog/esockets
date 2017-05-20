<?php

namespace Esockets\socket;

use Esockets\base\AbstractAddress;
use Esockets\base\exception\ReadException;
use Esockets\base\exception\SendException;

final class UdpClient extends AbstractSocketClient
{
    public function connect(AbstractAddress $serverAddress)
    {
        if ($this->connected) {
            throw new \LogicException('Socket is already connected.');
        }

        $this->serverAddress = $serverAddress;

        try {
            if ($this->send(1)) {
                $this->connected = true;
            }
        } catch (SendException $e) {
            ;
        }

        if (!$this->connected) {
            $this->errorHandler->handleError();
        } else {
            $this->eventConnect->callEvents();
        }
    }

    public function read(int $length, bool $force)
    {
        $buffer = null;
        /*$ip = null;
        $port = 0;*/
        if ($this->isUnixAddress()) {
            $addr = $this->serverAddress->getSockPath();
            $port = 0;
        } else {
            $addr = $this->serverAddress->getIp();
            $port = $this->serverAddress->getPort();
        }
        $bytes = socket_recvfrom($this->socket, $buffer, $length, 0, $addr, $port);
        if ($bytes === false) {
            throw new ReadException('Fail while reading data from udp socket.', ReadException::ERROR_FAIL);
        } elseif ($bytes === 0) {
            throw new ReadException('0 bytes read from udp socket.', ReadException::ERROR_EMPTY);
        }

        return $buffer;
    }

    public function send($data): bool
    {
        if ($this->isUnixAddress()) {
            $addr = $this->serverAddress->getSockPath();
            $port = 0;
        } else {
            $addr = $this->serverAddress->getIp();
            $port = $this->serverAddress->getPort();
        }
        $wrote = socket_sendto($this->socket, $data, strlen($data), 0, $addr, $port);
        if ($wrote === false) {
            return false;
        } elseif ($wrote === 0) {
            throw new SendException('Not sended!');
        }
        $this->transmittedBytes += $wrote;
        $this->transmittedPackets++;
        return true;
    }
}
