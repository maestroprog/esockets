<?php

namespace Esockets\socket;

use Esockets\base\AbstractAddress;
use Esockets\base\AbstractClient;
use Esockets\base\BlockingInterface;
use Esockets\base\CallbackEvent;
use Esockets\base\CallbackEventsContainer;
use Esockets\base\exception\ConnectionException;
use Esockets\base\PingPacket;

abstract class AbstractSocketClient extends AbstractClient implements BlockingInterface
{
    use SocketTrait;

    protected $socketDomain;
    protected $socket;
    protected $connected = false;
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


    /** Интервал времени ожидания между попытками при чтении/записи. */
    const SOCKET_WAIT = 1;

    /** Константы операций ввода/вывода. */
    const OP_READ = 0;
    const OP_WRITE = 1;

    /**
     * @param int $socketDomain
     * @param SocketErrorHandler $errorHandler
     * @return AbstractSocketClient
     */
    public static function createEmpty(int $socketDomain, SocketErrorHandler $errorHandler): self
    {
        return new static($socketDomain, $errorHandler);
    }

    /**
     * @param int $socketDomain
     * @param SocketErrorHandler $errorHandler
     * @param resource $socket
     * @return AbstractSocketClient
     * @throws ConnectionException
     */
    public static function createConnected(
        int $socketDomain,
        SocketErrorHandler $errorHandler,
        resource $socket = null
    ): self
    {
        if (get_resource_type($socket) !== 'socket') {
            throw new ConnectionException('Unknown resource type: ' . get_resource_type($socket));
        }
        return new static($socketDomain, $errorHandler, $socket);
    }

    final private function __construct(int $socketDomain, SocketErrorHandler $errorHandler, resource $socket = null)
    {
        $this->eventConnect = new CallbackEventsContainer();
        $this->eventDisconnect = new CallbackEventsContainer();

        $this->socketDomain = $socketDomain;
        $this->errorHandler = $errorHandler;
        if (is_null($socket)) {
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
            if (!($this->socket = socket_create($socketDomain, $type, $protocol))) {
                $this->errorHandler->handleError();
            } else {
                $this->errorHandler->setSocket($this->socket);
            }
        } else {
            $this->socket = $socket;
            $this->connected = true;
            $this->errorHandler->setSocket($this->socket);
        }
    }

    public function getServerAddress(): AbstractAddress
    {
        return $this->serverAddress;
    }

    public function getClientAddress(): AbstractAddress
    {
        if (is_null($this->clientAddress) || !($this->clientAddress instanceof AbstractAddress)) {
            $addr = $port = null;
            socket_getsockname($this->socket, $addr, $port);
            if ($this->socketDomain === AF_UNIX) {
                $this->clientAddress = new Ipv4Address($addr, $port);
            } else {
                $this->clientAddress = new UnixAddress($addr);
            }
        }
        return $this->clientAddress;
    }

    /**
     * @inheritDoc
     */
    public function getConnectionResource()
    {
        return $this->socket;
    }

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

    public function onConnect(callable $callback): CallbackEvent
    {
        return $this->eventConnect->addEvent(CallbackEvent::create($callback));
    }

    public function isConnected(): bool
    {
        return $this->connected;
    }

    public function disconnect()
    {
        if ($this->socket) {
            $this->block(); // блокируем сокет перед завершением его работы
            socket_shutdown($this->socket);
            socket_close($this->socket);
            $this->eventDisconnect->callEvents();
        } else {
            throw new \LogicException('Socket already is closed.');
        }
    }

    public function onDisconnect(callable $callback): CallbackEvent
    {
        return $this->eventDisconnect->addEvent(CallbackEvent::create($callback));
    }

    public function block()
    {
        socket_set_block($this->socket);
    }

    public function unblock()
    {
        socket_set_nonblock($this->socket);
    }

    public function getReceivedBytesCount(): int
    {
        return $this->receivedBytes;
    }

    public function getReceivedPacketCount(): int
    {
        return $this->receivedPackets;
    }

    public function getTransmittedBytesCount(): int
    {
        return $this->transmittedBytes;
    }

    public function getTransmittedPacketCount(): int
    {
        return $this->transmittedPackets;
    }

    /**
     * Поддерживает жизнь соединения.
     * Что делает:
     * - контролирует текущее состояние соединения,
     * - проверяет связь с заданным интервалом,
     * - выполняет чтение входящих данных,
     * - выполняет переподключение при обрыве связи, если это включено,
     *
     * Возвращает true, если сокет жив, false если не работает.
     * Можно использовать в бесконечном цикле:
     * while ($NET->live()) {
     *     // тут делаем что-то.
     * }
     *
     * @return bool
     *
     * public function live()
     * {
     * // TODO: Implement live() method.
     * }
     */
    /*
        public function ping()
        {
            $ping = PingPacket::request(rand(1000, 9999));
            $this->eventPong = function (PingPacket $msg) use ($ping) {
                if ($msg->getValue() !== $ping->getValue()) {
                    throw new \Exception('Incorrect ping data');
                } else {
                    $this->setTime(self::LIVE_LAST_PING);
                }
                $this->eventPong;
            };
            $this->send($ping);
            unset($ping);
        }

        public function pong(PingPacket $pingData)
        {
            // TODO: Implement pong() method.
        }*/


    final private function __clone()
    {
        ;
    }

    final private function __sleep()
    {
        ;
    }
}
