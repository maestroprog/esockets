<?php

namespace Esockets\socket;

use Esockets\base\AbstractAddress;
use Esockets\base\AbstractServer;
use Esockets\base\BlockingInterface;
use Esockets\base\CallbackEvent;
use Esockets\base\CallbackEventsContainer;
use Esockets\base\exception\ConnectionException;
use Esockets\base\exception\ReadException;

final class UdpServer extends AbstractServer implements BlockingInterface
{
    use SocketTrait;

    /**
     * @var Ipv4Address|UnixAddress
     */
    protected $listenAddress;
    protected $socket;
    protected $connected = false;

    protected $errorHandler;

    protected $eventConnect;
    protected $eventDisconnect;
    protected $eventFound;

    public function __construct(int $socketDomain, SocketErrorHandler $errorHandler)
    {
        $this->socketDomain = $socketDomain;
        $this->errorHandler = $errorHandler;

        $this->eventConnect = new CallbackEventsContainer();
        $this->eventDisconnect = new CallbackEventsContainer();
        $this->eventFound = new CallbackEventsContainer();

        $this->errorHandler = $errorHandler;
        if (!($this->socket = socket_create($socketDomain, SOCK_DGRAM, SOL_UDP))) {
            throw new ConnectionException(socket_strerror(socket_last_error()));
        } else {
            $this->errorHandler->setSocket($this->socket);
        }
        socket_set_option($this->socket, SOL_UDP, SO_REUSEADDR, 1);
    }

    /**
     * @inheritDoc
     */
    public function connect(AbstractAddress $listenAddress)
    {
        $this->listenAddress = $listenAddress;

        if ($this->connected) {
            throw new \LogicException('Socket is already connected.');
        }
        if ($this->isIpAddress() && $listenAddress instanceof Ipv4Address) {
            if (socket_bind($this->socket, $listenAddress->getIp(), $listenAddress->getPort())) {
                $this->connected = true;
            }
        } elseif ($this->isUnixAddress() && $listenAddress instanceof UnixAddress) {
            if (socket_bind($this->socket, $listenAddress->getSockPath())) {
                $this->connected = true;
            }
        } else {
            throw new \LogicException('Unknown socket domain.');
        }

        if (!$this->connected) {
            $this->errorHandler->handleError();
        } else {
            $this->eventConnect->callEvents();
        }

        if (!socket_listen($this->socket)) {
            $this->errorHandler->handleError();
        } else {
            $this->unblock();
        }
    }

    public function onConnect(callable $callback): CallbackEvent
    {
        return $this->eventConnect->addEvent(CallbackEvent::create($callback));
    }

    public function reconnect(): bool
    {
        $this->disconnect();
        try {

            $this->connect($this->listenAddress);
        } catch (ConnectionException $e) {
            return false;
        }
        return true;
    }

    public function isConnected(): bool
    {
        return $this->connected;
    }

    public function disconnect()
    {
        if ($this->isUnixAddress()) {
            if (file_exists($this->listenAddress->getSockPath())) {
                unlink($this->listenAddress->getSockPath());
            } else {
                throw new \LogicException(sprintf(
                    'Pipe file "%s" not found',
                    $this->listenAddress->getSockPath()
                ));
            }
        }
        $this->eventDisconnect->callEvents();
    }

    public function onDisconnect(callable $callback): CallbackEvent
    {
        return $this->eventDisconnect->addEvent(CallbackEvent::create($callback));
    }

    public function find()
    {
        $address = null;
        $port = 0;
        $bytes = socket_recvfrom($this->socket, $buffer, 1, 0, $address, $port);
        if ($bytes === false) {
            throw new ReadException('Fail while reading data from udp socket.', ReadException::ERROR_FAIL);
        } elseif ($bytes === 0) {
            throw new ReadException('0 bytes read from udp socket.', ReadException::ERROR_EMPTY);
        }
        if ($this->isIpAddress()) {
            $clientAddress = new Ipv4Address($address, $port);
        } else {
            $clientAddress = new UnixAddress($address);
        }
        $this->eventFound->callEvents($clientAddress);
    }

    public function onFound(callable $callback): CallbackEvent
    {
        return $this->eventFound->addEvent(CallbackEvent::create($callback));
    }

    public function block()
    {
        socket_set_block($this->socket);
    }

    public function unblock()
    {
        socket_set_nonblock($this->socket);
    }
}
