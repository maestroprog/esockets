<?php

namespace Esockets\socket;

use Esockets\base\AbstractAddress;
use Esockets\base\AbstractConnectionResource;
use Esockets\base\AbstractServer;
use Esockets\base\BlockingInterface;
use Esockets\base\CallbackEventListener;
use Esockets\base\Event;
use Esockets\base\ClientsContainerInterface;
use Esockets\base\exception\ConnectionException;
use Esockets\base\HasClientsContainer;
use Esockets\Client;

/**
 * Простая реализация TCP сервера.
 * После создания слушающего сокета он автоматически переключается в неболокирующий режим.
 */
final class TcpServer extends AbstractServer implements BlockingInterface, HasClientsContainer
{
    use SocketTrait;

    private $maxConn;
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

    /**
     * @param int $socketDomain Домен сокета
     * @param int $maxConn Максимальное количество соединений в очереди (параметр backlog)
     * @param SocketErrorHandler $errorHandler
     * @param ClientsContainerInterface $clientsContainer
     * @throws ConnectionException
     */
    public function __construct(
        int $socketDomain,
        int $maxConn,
        SocketErrorHandler $errorHandler,
        ClientsContainerInterface $clientsContainer
    )
    {
        $this->socketDomain = $socketDomain;
        $this->maxConn = $maxConn;
        $this->errorHandler = $errorHandler;
        $this->clientsContainer = $clientsContainer;

        $this->eventConnect = new Event();
        $this->eventDisconnect = new Event();
        $this->eventFound = new Event();

        if (!($this->socket = socket_create($socketDomain, SOCK_STREAM, SOL_TCP))) {
            throw new ConnectionException(socket_strerror(socket_last_error()));
        } else {
            $this->errorHandler->setSocket($this->socket);
        }
        socket_set_option($this->socket, SOL_SOCKET, SO_REUSEADDR, 1);
        $this->connectionResource = new SocketConnectionResource($this->socket);
    }

    /**
     * @inheritdoc
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
            $this->eventConnect->call();
        }

        if (!socket_listen($this->socket, $this->maxConn)) {
            $this->errorHandler->handleError();
        } else {
            $this->unblock();
        }
    }

    /**
     * @inheritdoc
     */
    public function onConnect(callable $callback): CallbackEventListener
    {
        return $this->eventConnect->attachCallbackListener($callback);
    }

    /**
     * @inheritdoc
     */
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

    /**
     * @inheritdoc
     */
    public function isConnected(): bool
    {
        return $this->connected;
    }

    /**
     * @inheritdoc
     */
    public function disconnect()
    {
        if (!is_resource($this->socket) || get_resource_type($this->socket) !== 'Socket') {
            return;
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
        $this->eventDisconnect->call();
    }

    /**
     * @inheritdoc
     */
    public function onDisconnect(callable $callback): CallbackEventListener
    {
        return $this->eventDisconnect->attachCallbackListener($callback);
    }

    /**
     * @inheritdoc
     */
    public function getConnectionResource(): AbstractConnectionResource
    {
        return $this->connectionResource;
    }

    /**
     * @inheritdoc
     */
    public function getClientsContainer(): ClientsContainerInterface
    {
        return $this->clientsContainer;
    }

    /**
     * @inheritdoc
     */
    public function find()
    {
        /**
         * @var $connectionsIndex Client[]
         */
        // собираем массив всех подключений
        $connectionsIndex = [];
        $connections = array_map(function (Client $client) use (&$connectionsIndex) {
            $resource = $client->getConnectionResource()->getResource();
            $connectionsIndex[(int)$resource] = $client;
            return $resource;
        }, $this->clientsContainer->list());
        $write = $except = [];
        $connections[-1] = $this->socket;

        // socket_select() отбирает активные соединения
        if (false === ($changed = socket_select($connections, $write, $except, 1))) {
            $this->errorHandler->handleError();
        } elseif ($changed > 0) {
            // пройдёмся по активным соединениям
            foreach ($connections as $idx => $readConnection) {
                if ($idx > -1) {
                    // чтение из подключенных сокетов (клиентов)
                    $connectionsIndex[(int)$readConnection]->read();
                } elseif ($connection = socket_accept($this->socket)) {
                    // принятие новых соединений к серверному сокету
                    $this->eventFound->call(new SocketConnectionResource($connection));
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

    /**
     * @inheritdoc
     */
    public function onFound(callable $callback): CallbackEventListener
    {
        return $this->eventFound->attachCallbackListener($callback);
    }

    /**
     * @inheritdoc
     */
    public function block()
    {
        socket_set_block($this->socket);
    }

    /**
     * @inheritdoc
     */
    public function unblock()
    {
        socket_set_nonblock($this->socket);
    }
}
