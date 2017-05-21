<?php

namespace Esockets\socket;

use Esockets\base\AbstractAddress;
use Esockets\base\AbstractServer;
use Esockets\base\BlockingInterface;
use Esockets\base\CallbackEvent;
use Esockets\base\CallbackEventsContainer;
use Esockets\base\exception\ConnectionException;

/**
 * Простая реализация Tcp сервера.
 * После создания слушающего сокета,
 * сервер автоматически переключает его в неболокирующий режим.
 */
final class TcpServer extends AbstractServer implements BlockingInterface
{
    use SocketTrait;

    /**
     * @var Ipv4Address|UnixAddress
     */
    protected $listenAddress;
    protected $socket;
    protected $connected = false;

    protected $errorHandler;

    private $eventConnect;
    private $eventDisconnect;
    private $eventFound;

    public function __construct(int $socketDomain, SocketErrorHandler $errorHandler)
    {
        $this->socketDomain = $socketDomain;

        $this->eventConnect = new CallbackEventsContainer();
        $this->eventDisconnect = new CallbackEventsContainer();
        $this->eventFound = new CallbackEventsContainer();

        $this->errorHandler = $errorHandler;
        if (!($this->socket = socket_create($socketDomain, SOCK_STREAM, SOL_TCP))) {
            throw new ConnectionException(socket_strerror(socket_last_error()));
        } else {
            $this->errorHandler->setSocket($this->socket);
        }
        socket_set_option($this->socket, SOL_SOCKET, SO_REUSEADDR, 1);
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

    public function find()
    {
        if ($connection = socket_accept($this->socket)) {
            $this->eventFound->callEvents($connection);
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
    }*/

    public function block()
    {
        socket_set_block($this->socket);
    }

    public function unblock()
    {
        socket_set_nonblock($this->socket);
    }
}
