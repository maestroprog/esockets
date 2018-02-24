<?php

namespace Esockets\Socket;

use Esockets\Base\AbstractAddress;
use Esockets\Base\AbstractConnectionResource;
use Esockets\Base\AbstractServer;
use Esockets\Base\BlockingInterface;
use Esockets\Base\CallbackEventListener;
use Esockets\Base\ClientsContainerInterface;
use Esockets\Base\Event;
use Esockets\Base\Exception\ConnectionException;
use Esockets\Base\HasClientsContainer;
use Esockets\Client;

/**
 * Простая реализация TCP сервера.
 * После создания слушающего сокета он автоматически переключается в неболокирующий режим.
 */
final class TcpServer extends AbstractServer implements BlockingInterface, HasClientsContainer
{
    use SocketTrait;

    private $maxConn;
    private $timeoutSeconds;
    private $timeoutMicroseconds;
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

    private $blocked = true;

    /**
     * @param int $socketDomain Домен сокета
     * @param int $maxConn Максимальное количество соединений в очереди (параметр backlog)
     * @param int $waitInterval Время ожидания изменившихся соединений для системного вызова select() в миллисекундах
     * @param SocketErrorHandler $errorHandler
     * @param ClientsContainerInterface $clientsContainer
     *
     * @throws ConnectionException
     */
    public function __construct(
        int $socketDomain,
        int $maxConn,
        int $waitInterval,
        SocketErrorHandler $errorHandler,
        ClientsContainerInterface $clientsContainer
    )
    {
        $this->socketDomain = $socketDomain;
        $this->maxConn = $maxConn;
        $this->timeoutSeconds = (int)floor($waitInterval / 1000);
        $this->timeoutMicroseconds = $waitInterval * 1000 - $this->timeoutSeconds * 1000000;
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
        socket_set_option($this->socket, SOL_TCP, TCP_NODELAY, 1);
        $this->connectionResource = new SocketConnectionResource($this->socket);
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
    public function disconnect(): void
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
    public function connect(AbstractAddress $listenAddress): void
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
    public function block(): void
    {
        $this->blocked = true;
        socket_set_block($this->socket);
    }

    /**
     * @inheritdoc
     */
    public function unblock(): void
    {
        $this->blocked = false;
        socket_set_nonblock($this->socket);
    }

    /**
     * @inheritdoc
     */
    public function isBlocked(): bool
    {
        return $this->blocked;
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
    public function find(): void
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

        $accepted = new \SplQueue();

        // socket_select() отбирает активные соединения
        $changed = socket_select($connections, $write, $except, $this->timeoutSeconds, $this->timeoutMicroseconds);
        if (false === $changed) {
            $this->errorHandler->handleError();
        } elseif ($changed > 0) {
            // пройдёмся по активным соединениям
            foreach ($connections as $idx => $readConnection) {
                if ($idx > -1) {
                    // чтение из подключенных сокетов (клиентов)
                    $connectionsIndex[(int)$readConnection]->read();
                } elseif ($connection = socket_accept($this->socket)) {
                    // принятие новых соединений к серверному сокету
                    $accepted->enqueue($connection);
                }
            }
            foreach ($write as $writeConnection) {
                // todo ?
                throw new \ErrorException('Write exception.');
            }
            foreach ($except as $exceptConnection) {
                // todo ?
                throw new \ErrorException('Socket exception.');
            }
        }
        // Обрабатываем новые входящие подключенния
        foreach ($accepted as $connection) {
            $this->eventFound->call(new SocketConnectionResource($connection));
        }
        // Обрабатываем существующие подключения
        foreach ($connectionsIndex as $client) {
            if (!$client->live()) {
                $client->disconnect();
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
}
