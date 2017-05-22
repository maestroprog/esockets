<?php

namespace Esockets\socket;

use Esockets\base\AbstractAddress;
use Esockets\base\AbstractConnectionResource;
use Esockets\base\AbstractServer;
use Esockets\base\BlockingInterface;
use Esockets\base\CallbackEvent;
use Esockets\base\CallbackEventsContainer;
use Esockets\base\ClientsContainerInterface;
use Esockets\base\exception\ConnectionException;
use Esockets\base\exception\ReadException;
use Esockets\base\HasClientsContainer;

final class UdpServer extends AbstractServer implements BlockingInterface, HasClientsContainer
{
    use SocketTrait;

    /**
     * @var Ipv4Address|UnixAddress
     */
    private $listenAddress;
    private $socket;
    /**
     * @var SocketConnectionResource|AbstractConnectionResource
     */
    private $connectionResource;
    private $connected = false;

    private $errorHandler;
    private $clientsContainer;

    private $eventConnect;
    private $eventDisconnect;
    private $eventFound;

    public function __construct(
        int $socketDomain,
        SocketErrorHandler $errorHandler,
        ClientsContainerInterface $clientsContainer
    )
    {
        $this->socketDomain = $socketDomain;
        $this->errorHandler = $errorHandler;
        $this->clientsContainer = $clientsContainer;

        $this->eventConnect = new CallbackEventsContainer();
        $this->eventDisconnect = new CallbackEventsContainer();
        $this->eventFound = new CallbackEventsContainer();

        if (!($this->socket = socket_create($socketDomain, SOCK_DGRAM, SOL_UDP))) {
            throw new ConnectionException(socket_strerror(socket_last_error()));
        } else {
            $this->errorHandler->setSocket($this->socket);
        }
        socket_set_option($this->socket, SOL_SOCKET, SO_REUSEADDR, 1);
        $this->connectionResource = new SocketConnectionResource($this->socket);
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

        $this->unblock();
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

    /**
     * @inheritDoc
     */
    public function getConnectionResource(): AbstractConnectionResource
    {
        return $this->socket;
    }

    /**
     * @inheritDoc
     */
    public function getClientsContainer(): ClientsContainerInterface
    {
        return $this->clientsContainer;
    }

    public function find()
    {
        $address = null;
        $port = 0;
        $bytes = socket_recvfrom($this->socket, $buffer, 1500, 0, $address, $port);
        if ($bytes === false) {
//            throw new ReadException('Fail while reading data from udp socket.', ReadException::ERROR_FAIL);
            $this->errorHandler->handleError();
            return;
        } elseif ($bytes === 0) {
            throw new ReadException('0 bytes read from udp socket.', ReadException::ERROR_EMPTY);
        }

        if ($this->isIpAddress()) {
            $clientAddress = new Ipv4Address($address, $port);
        } else {
            $clientAddress = new UnixAddress($address);
        }

        if (!$this->clientsContainer->existsByAddress($clientAddress)) {
            $this->eventFound->callEvents(new VirtualUdpConnection(
                $this->socketDomain,
                new SocketConnectionResource(
                    $this->socket
                ),
                $clientAddress,
                []
            ));
        } elseif (!($bytes === 1 && $buffer == 1)) {
            $client = $this->clientsContainer->getByAddress($clientAddress);
            $connectionResource = $client->getConnectionResource();
            if (!$connectionResource instanceof VirtualUdpConnection) {
                throw new \LogicException('Unknown connection resource.');
            }
            $connectionResource->addToBuffer($buffer);
        }
        foreach ($this->clientsContainer->list() as $client) {
            $connectionResource = $client->getConnectionResource();
            if (!$connectionResource instanceof VirtualUdpConnection) {
                throw new \LogicException('Unknown connection resource.');
            }
            if ($connectionResource->getBufferLength() > 0) {
                $client->read();
            }
        }
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
