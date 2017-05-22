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
            $this->send(1);
            $this->connected = true;
        } catch (SendException $e) {

        }

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
        if ($this->connectionResource instanceof VirtualUdpConnection) {
            /*$ip = null;
            $port = 0;*/
            try {
                $buffer = $this->connectionResource->read();
            } catch (SendException $e) {
                //$this->errorHandler->handleError();
                $this->disconnect();
            } finally {
                $dataLength = strlen($buffer);
                $this->receivedBytes += $dataLength;
                $this->receivedPackets++;
                return $buffer;
            }
        } else {
            $address = null;
            $port = 0;
            $bytes = socket_recvfrom($this->socket, $buffer, $length, 0, $address, $port);
            if ($bytes === false || $bytes === 0) {
                $this->errorHandler->handleError();
            } else {
                if ($this->isUnixAddress()) {
                    $address = new UnixAddress($address);
                } else {
                    $address = new Ipv4Address($address, $port);
                }
                if (!$this->getPeerAddress()->equalsTo($address)) {
                    throw new ReadException('Unknown peer: ' . $address);
                }
                $dataLength = strlen($buffer);
                $this->receivedBytes += $dataLength;
                $this->receivedPackets++;
            }
            return $buffer;
        }
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
