<?php

namespace Esockets\socket;

use Esockets\base\AbstractAddress;
use Esockets\base\AbstractClient;
use Esockets\base\BlockingInterface;
use Esockets\base\exception\ConnectionException;
use Esockets\base\PingPacket;

class AbstractSocketClient extends AbstractClient implements BlockingInterface
{
    /**
     * @var int type of socket
     */
    protected $socketDomain;
    protected $socket;

    protected $serverAddress;
    protected $clientAddress;


    /** Интервал времени ожидания между попытками при чтении/записи. */
    const SOCKET_WAIT = 1;

    /** Константы операций ввода/вывода. */
    const OP_READ = 0;
    const OP_WRITE = 1;

    private $eventDisconnect;
    private $eventRead;
    private $eventPong;

    protected $errorHandler;

    /**
     * @param int $socketDomain
     * @param SocketErrorHandler $errorHandler
     * @return AbstractSocketClient
     */
    public static function createEmpty(int $socketDomain, SocketErrorHandler $errorHandler): self
    {
        return new self($socketDomain, $errorHandler);
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
        return new self($socketDomain, $errorHandler, $socket);
    }

    final private function __construct(int $socketDomain, SocketErrorHandler $errorHandler, resource $socket = null)
    {
        $this->socketDomain = $socketDomain;
        $this->errorHandler = $errorHandler;
        if (is_null($socket)) {
            if (!($this->socket = socket_create($socketDomain, SOCK_STREAM, SOL_TCP))) {
                $this->errorHandler->handleError();
            } else {
                $this->errorHandler->setSocket($this->socket);
            }
        } else {
            $this->socket = $socket;
            $this->errorHandler->setSocket($socket);
        }
    }

    protected function isUnixAddress(): bool
    {
        return $this->socketDomain === AF_UNIX;
    }

    protected function isIpAddress(): bool
    {
        return $this->socketDomain === AF_INET || $this->socketDomain === AF_INET6;
    }

    /**
     * @inheritdoc
     */
    public function getServerAddress(): AbstractAddress
    {
        return $this->serverAddress;
    }

    /**
     * Вернет адрес клиента, который подключен к серверу.
     *
     * @return AbstractAddress
     */
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

    public function reconnect(): bool
    {
        $this->disconnect();
        try {
            $this->connect($this->serverAddress);
        } catch ($e) {
            return false;
        }
        return true;
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
     */
    public function live()
    {
        // TODO: Implement live() method.
    }

    public function onConnect(callable $callback)
    {
        // TODO: Implement onConnect() method.
    }

    public function isConnected(): bool
    {
        // TODO: Implement isConnected() method.
    }

    public function disconnect()
    {
        if ($this->socket) {
            $this->block(); // блокируем сокет перед завершением его работы
            socket_shutdown($this->socket);
            socket_close($this->socket);
            $this->callDisconnectEvent();
        } else {
            throw new \LogicException('Socket already is closed.');
        }
    }

    public function onDisconnect(callable $callback)
    {
        $this->eventDisconnect = $callback;
    }

    protected function callDisconnectEvent()
    {
        if (is_callable($this->eventDisconnect)) {
            call_user_func($this->eventDisconnect);
        }
    }

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
    }

    public function block()
    {
        socket_set_block($this->socket);
    }

    public function unblock()
    {
        socket_set_nonblock($this->socket);
    }

    final private function __clone()
    {
        ;
    }

    final private function __sleep()
    {
        ;
    }
}
