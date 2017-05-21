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

        $this->connected = true;

        if (!$this->connected) {
            $this->errorHandler->handleError();
        } else {
            $this->unblock();
            $this->eventConnect->callEvents();
        }
    }

    public function disconnect()
    {
        $this->connected = false;
        $this->eventDisconnect->callEvents();
    }


    public function read(int $length, bool $force)
    {
        $buffer = null;
        /*$ip = null;
        $port = 0;*/
        if ($this->isUnixAddress()) {
            $address = $this->serverAddress->getSockPath();
            $port = 0;
        } else {
            $address = $this->serverAddress->getIp();
            $port = $this->serverAddress->getPort();
        }
        $bytes = socket_recvfrom($this->socket, $buffer, $length, 0, $address, $port);
        if ($bytes === false) {
            $this->errorHandler->handleError();
            return false;
        } elseif ($bytes === 0) {
            var_dump($bytes, $length, $buffer);
            $this->errorHandler->handleError();
            throw new ReadException('0 bytes read from udp socket.', ReadException::ERROR_EMPTY);
        }

        return $buffer;
    }

    public function send($data): bool
    {
        if ($this->isUnixAddress()) {
            $address = $this->serverAddress->getSockPath();
            $port = 0;
        } else {
            $address = $this->serverAddress->getIp();
            $port = $this->serverAddress->getPort();
        }
        $wrote = socket_sendto($this->socket, $data, strlen($data), 0, $address, $port);
        if ($wrote === false) {
            return false;
        } elseif ($wrote === 0) {
            throw new SendException('Not transmitted!');
        }
        $this->transmittedBytes += $wrote;
        $this->transmittedPackets++;
        return true;
    }
}
