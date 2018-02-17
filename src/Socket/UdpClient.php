<?php

namespace Esockets\Socket;

use Esockets\Base\AbstractAddress;
use Esockets\Base\Exception\ReadException;
use Esockets\Base\Exception\SendException;

/**
 * Класс UDP клиента.
 * Как и класс TCP клиента, может использоваться как в качестве обхекта клиента,
 * так и в качестве объекта пира (серверного сокета, взаимодействующего с удалённым клиентом).
 */
final class UdpClient extends AbstractSocketClient
{
    /**
     * @inheritdoc
     */
    public function connect(AbstractAddress $serverAddress): void
    {
        if ($this->connected) {
            throw new \LogicException('Socket is already connected.');
        }
        $this->createSocket();

        $this->serverAddress = $serverAddress;

        try {
            // отправляем один байт с числом "1" в качестве приветствия
            $this->send(1);
            $this->connected = true;
        } catch (SendException $e) {
            ; // чтобы скрипт не падал перехватим исключение
        }

        if (!$this->connected) {
            $this->errorHandler->handleError();
        } else {
            $this->unblock();
            $this->eventConnect->call();
        }
    }

    /**
     * @inheritdoc
     * todo добавить контроль кол-ва отправленынх байт
     */
    public function send($data): bool
    {
        if ($this->isUnixAddress()) {
            $address = $this->serverAddress->getSockPath();
            $port = 0;
        } else {
            $address = $this->serverAddress->getIp();
            $port = $this->serverAddress->getPort();
        }
        $length = strlen($data);
        $wrote = socket_sendto($this->socket, $data, $length, 0, $address, $port);
        if ($wrote === false) {
            return false;
        } elseif ($wrote === 0 || $wrote < $length) {
            throw new SendException('Not transmitted!');
        }
        $this->transmittedBytes += $wrote;
        $this->transmittedPackets++;
        return true;
    }

    /**
     * @inheritdoc
     */
    public function disconnect(): void
    {
        if (!$this->connected) {
            return;
        }
        $this->connected = false;
        $this->eventDisconnect->call();
    }

    /**
     * @inheritdoc
     */
    public function getReadBufferSize(): int
    {
        return 65535;
    }

    /**
     * @inheritdoc
     * todo реализовать опцию $force
     */
    public function read(int $length, bool $force)
    {
        $buffer = null;
        if ($this->connectionResource instanceof VirtualUdpConnection) {
            $buffer = $this->connectionResource->read();
            $dataLength = strlen($buffer);
            $this->receivedBytes += $dataLength;
            $this->receivedPackets++;

            return $buffer;
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
                    // если вдруг в сокет придёт дейтаграмма с левого адреса
                    throw new ReadException('Unknown peer: ' . $address, ReadException::ERROR_FAIL);
                }
                $dataLength = strlen($buffer);
                $this->receivedBytes += $dataLength;
                $this->receivedPackets++;
            }
            return $buffer;
        }
    }

    /**
     * @inheritdoc
     */
    public function getMaxPacketSizeForWriting(): int
    {
        return 65507;
    }
}
