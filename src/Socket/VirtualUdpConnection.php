<?php

namespace Esockets\Socket;

use Esockets\Base\AbstractAddress;
use Esockets\Base\AbstractConnectionResource;
use Esockets\Base\Exception\SendException;

/**
 * Класс костыльного UDP соединения.
 * Todo избавиться от такого костыля.
 */
final class VirtualUdpConnection extends AbstractConnectionResource
{
    use SocketTrait;

    private $connectionResource;
    private $id;
    private $peerAddress;
    private $buffer = [];

    /**
     * VirtualUdpConnection constructor.
     *
     * @param int $socketDomain
     * @param SocketConnectionResource $connectionResource
     * @param $peerAddress AbstractAddress|Ipv4Address|UnixAddress
     * @param array $buffer
     */
    public function __construct(
        int $socketDomain,
        SocketConnectionResource $connectionResource,
        AbstractAddress $peerAddress,
        array $buffer
    )
    {
        static $id = 0;
        $this->id = ++$id;
        $this->socketDomain = $socketDomain;
        $this->connectionResource = $connectionResource;
        $this->peerAddress = $peerAddress;
        $this->buffer = $buffer;
    }

    /**
     * @inheritDoc
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return resource Socket resource
     */
    public function getResource()
    {
        return $this->connectionResource->getResource();
    }

    /**
     * @return AbstractAddress|Ipv4Address|UnixAddress
     */
    public function getPeerAddress(): AbstractAddress
    {
        return $this->peerAddress;
    }

    public function addToBuffer($data)
    {
        array_push($this->buffer, $data);
    }

    public function getBufferLength(): int
    {
        return count($this->buffer);
    }

    public function read()
    {
        if (count($this->buffer) === 0) {
            return null;
        }
        return array_shift($this->buffer);
    }

    /**
     * @param $data
     *
     * @return bool
     * @throws SendException
     */
    public function send($data): bool
    {
        $dataLength = strlen($data);
        if ($dataLength === 0) {
            throw new \RuntimeException('Can not send an empty package.');
        }
        if ($this->isIpAddress()) {
            $address = $this->peerAddress->getIp();
            $port = $this->peerAddress->getPort();
        } else {
            $address = $this->peerAddress->getSockPath();
            $port = 0;
        }
        $wrote = socket_sendto($this->connectionResource->getResource(), $data, strlen($data), 0, $address, $port);
        if ($wrote === false || $wrote === 0) {
//            throw new SendException('I don\'t send :(');
            return false;
        } elseif ($wrote < $dataLength) {
            throw new SendException('Could not send the whole package.');
        }
        return true;
    }
}
