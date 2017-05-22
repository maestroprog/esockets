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
use Esockets\base\HasClientsContainer;
use Esockets\Client;
use Esockets\ClientsContainer;

/**
 * Простая реализация Tcp сервера.
 * После создания слушающего сокета,
 * сервер автоматически переключает его в неболокирующий режим.
 */
final class TcpServer extends AbstractServer implements BlockingInterface, HasClientsContainer
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

        if (!($this->socket = socket_create($socketDomain, SOCK_STREAM, SOL_TCP))) {
            throw new ConnectionException(socket_strerror(socket_last_error()));
        } else {
            $this->errorHandler->setSocket($this->socket);
        }
        socket_set_option($this->socket, SOL_SOCKET, SO_REUSEADDR, 1);
        $this->connectionResource = new SocketConnectionResource($this->socket);
    }

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
        if (!is_resource($this->socket) || get_resource_type($this->socket) !== 'Socket') {
            throw new \LogicException('Socket already is disconnected.');
        }
        socket_shutdown($this->socket);
        $this->block(); // блокируем сокет перед его закрытием
        socket_close($this->socket);

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
        return $this->connectionResource;
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
        if ($connection = socket_accept($this->socket)) {
            $this->eventFound->callEvents(new SocketConnectionResource($connection));
        }

        /**
         * @var $connectionsIndex Client[]
         */
        $connectionsIndex = [];
        $connections = array_map(function (Client $client) use (&$connectionsIndex) {
            $resource = $client->getConnectionResource()->getResource();
            $connectionsIndex[(int)$resource] = $client;
            return $resource;
        }, $this->clientsContainer->list());
        $write = $except = [];
        $connections[-1] = $this->socket;
        if (false === ($changed = socket_select($connections, $write, $except, 1))) {
            $this->errorHandler->handleError();
        } elseif ($changed > 0) {
            foreach ($connections as $idx => $readConnection) {
                if ($idx > -1) {
                    $connectionsIndex[(int)$readConnection]->read();
                }
            }
            foreach ($write as $writeConnection) {
                // todo ?
            }
            foreach ($except as $exceptConnection) {
                // todo
            }
        }
    }

    public function onFound(callable $callback): CallbackEvent
    {
        return $this->eventFound->addEvent(CallbackEvent::create($callback));
    }

    /*
    public function select()
    {
        $sockets = [$this->socket];
        foreach ($this->connections as $peer) {
            $sockets[] = $peer->getConnection();
        }
        $write = [];
        $except = [];
        if (false === ($rc = socket_select($sockets, $write, $except, null))) {
            throw new \Exception('socket_select failed!');
        }
    }
    */

    public function block()
    {
        socket_set_block($this->socket);
    }

    public function unblock()
    {
        socket_set_nonblock($this->socket);
    }
}
