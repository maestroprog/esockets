<?php

namespace Esockets\Socket;

use Esockets\Base\AbstractAddress;
use Esockets\Base\AbstractClient;
use Esockets\Base\AbstractConnectionResource;
use Esockets\Base\BlockingInterface;
use Esockets\Base\CallbackEventListener;
use Esockets\Base\Event;
use Esockets\Base\Exception\ConnectionException;

abstract class AbstractSocketClient extends AbstractClient implements BlockingInterface
{
    use SocketTrait;

    /** Интервал времени ожидания между попытками при чтении/записи. */
    const SOCKET_WAIT = 10;

    /** Константы операций ввода/вывода. */
    const OP_READ = 0;
    const OP_WRITE = 1;

    protected $socketDomain;
    protected $socket;
    protected $connected = false;
    protected $connectionResource;
    /**
     * @var Ipv4Address|UnixAddress
     */
    protected $serverAddress;
    protected $clientAddress;

    protected $errorHandler;
    protected $eventConnect;
    protected $eventDisconnect;

    protected $receivedBytes = 0;
    protected $receivedPackets = 0;
    protected $transmittedBytes = 0;
    protected $transmittedPackets = 0;

    protected $blocked = true;

    /**
     * Приватный конструктор.
     *
     * @param int $socketDomain
     * @param SocketErrorHandler $errorHandler
     * @param AbstractConnectionResource|null $connectionResource
     *
     * @throws ConnectionException
     */
    final private function __construct(
        int $socketDomain,
        SocketErrorHandler $errorHandler,
        AbstractConnectionResource $connectionResource = null
    )
    {
        $this->socketDomain = $socketDomain;
        $this->errorHandler = $errorHandler;

        $this->eventConnect = new Event();
        $this->eventDisconnect = new Event();

        if (null === $connectionResource) {
            $this->createSocket();
        } else {
            if (get_class($this) === UdpClient::class && !$connectionResource instanceof VirtualUdpConnection) {
                throw new \InvalidArgumentException(
                    'Unknown connection: ' . get_class($connectionResource) . '; this is not supported.'
                );
            }
            $this->connectionResource = $connectionResource;
            $this->socket = $connectionResource->getResource();
            $this->connected = true;
            $this->errorHandler->setSocket($this->socket);
            if (method_exists($connectionResource, 'getPeerAddress')) {
                $this->serverAddress = $connectionResource->getPeerAddress();
            } else {
                $address = $port = null;
                if (!socket_getpeername($this->socket, $address, $port)) {
                    $this->errorHandler->handleError();
                }
                if ($this->isUnixAddress()) {
                    $this->serverAddress = new UnixAddress($address);
                } else {
                    $this->serverAddress = new Ipv4Address($address, $port);
                }
            }
        }
        if (get_class($this) === TcpClient::class) {
            socket_set_option($this->socket, SOL_SOCKET, SO_KEEPALIVE, 1);
            socket_set_option($this->socket, SOL_TCP, TCP_NODELAY, 1);
        }
    }

    /**
     * Создаёт сокет.
     *
     * @throws ConnectionException
     */
    protected function createSocket()
    {
        $this->receivedBytes = 0;
        $this->receivedPackets = 0;
        $this->transmittedBytes = 0;
        $this->transmittedPackets = 0;
        switch (get_class($this)) {
            case TcpClient::class:
                $type = SOCK_STREAM;
                $protocol = SOL_TCP;
                break;
            case UdpClient::class:
                $type = SOCK_DGRAM;
                $protocol = SOL_UDP;
                break;
            default:
                throw new \LogicException('Other protocols not supported.');
        }
        if (!($this->socket = socket_create($this->socketDomain, $type, $protocol))) {
            $this->errorHandler->handleError();
        } else {
            $this->errorHandler->setSocket($this->socket);
        }
        $this->connectionResource = new SocketConnectionResource($this->socket);
    }

    /**
     * Создаёт объект с подготовленным для подключения сокетом.
     *
     * @param int $socketDomain
     * @param SocketErrorHandler $errorHandler
     *
     * @return AbstractSocketClient
     *
     * @throws ConnectionException
     */
    public static function createEmpty(int $socketDomain, SocketErrorHandler $errorHandler): self
    {
        return new static($socketDomain, $errorHandler);
    }

    /**
     * Создает объект с подключенным сокетом.
     *
     * @param int $socketDomain
     * @param SocketErrorHandler $errorHandler
     * @param AbstractConnectionResource $connectionResource
     *
     * @return AbstractSocketClient
     *
     * @throws ConnectionException|\LogicException
     */
    public static function createConnected(
        int $socketDomain,
        SocketErrorHandler $errorHandler,
        AbstractConnectionResource $connectionResource = null
    ): self
    {
        return new static($socketDomain, $errorHandler, $connectionResource);
    }

    /**
     * @inheritdoc
     */
    public function getPeerAddress(): AbstractAddress
    {
        if (null === $this->serverAddress || !($this->serverAddress instanceof AbstractAddress)) {
            $address = $port = null;
            socket_getpeername($this->socket, $address, $port);
            if ($this->isUnixAddress() === AF_UNIX) {
                $this->serverAddress = new UnixAddress($address);
            } else {
                $this->serverAddress = new Ipv4Address($address, $port);
            }
        }
        return $this->serverAddress;
    }

    /**
     * @inheritdoc
     */
    public function getClientAddress(): AbstractAddress
    {
        if (null === $this->clientAddress || !($this->clientAddress instanceof AbstractAddress)) {
            $address = $port = null;
            socket_getsockname($this->socket, $address, $port);
            if ($this->isUnixAddress() === AF_UNIX) {
                $this->clientAddress = new UnixAddress($address);
            } else {
                $this->clientAddress = new Ipv4Address($address, $port);
            }
        }
        return $this->clientAddress;
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
    public function reconnect(): bool
    {
        $this->disconnect();
        try {
            $this->connect($this->serverAddress);
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
        if (!$this->connected || !is_resource($this->socket) || get_resource_type($this->socket) !== 'Socket') {
            return;
        }
        $this->connected = false;
        $this->eventDisconnect->call();
        $this->block(); // блокируем сокет перед его закрытием
        socket_shutdown($this->socket);
        socket_close($this->socket);
    }

    public function ready(): bool
    {
        $read = [$this->socket];
        $write = $except = [];

        return socket_select($read, $write, $except, 0, 0);
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
    public function getReceivedBytesCount(): int
    {
        return $this->receivedBytes;
    }

    /**
     * @inheritdoc
     */
    public function getReceivedPacketCount(): int
    {
        return $this->receivedPackets;
    }

    /**
     * @inheritdoc
     */
    public function getTransmittedBytesCount(): int
    {
        return $this->transmittedBytes;
    }

    /**
     * @inheritdoc
     */
    public function getTransmittedPacketCount(): int
    {
        return $this->transmittedPackets;
    }

    final private function __clone()
    {
        return null;
    }

    final private function __sleep()
    {
        ;
    }
}
