<?php

namespace Esockets;

use Esockets\base\AbstractAddress;
use Esockets\base\AbstractClient;
use Esockets\base\AbstractConnectionResource;
use Esockets\base\AbstractProtocol;
use Esockets\base\BlockingInterface;
use Esockets\base\CallbackEventListener;
use Esockets\base\ConnectorInterface;
use Esockets\base\PingPacket;
use Esockets\base\PingSupportInterface;
use Esockets\base\ReaderInterface;
use Esockets\base\SenderInterface;

/**
 * Враппер над связкой протокол-клиент.
 */
class Client implements ConnectorInterface, ReaderInterface, SenderInterface, BlockingInterface
{
    private $connection;
    private $protocol;

    private $timeout = 30;
    private $pingInterval = 5;
    private $reconnectInterval = 1;
    private $reconnectSupport = false;

    const TIME_LAST_CHECK = 'last_check'; // время успешной работы соединения
    const TIME_LAST_PING = 'last_ping'; // время последнего пинга
    const TIME_LAST_RECONNECT = 'last_reconnect'; // время последней попытки реконнекта

    private $times = [];

    public function __construct(AbstractClient $connection, AbstractProtocol $protocol)
    {
        $this->connection = $connection;
        $this->protocol = $protocol;

        $this->resetTime();
        $this->protocol->onReceive(function () {
            $this->resetTime();
        });
    }

    public function getPeerAddress(): AbstractAddress
    {
        return $this->connection->getPeerAddress();
    }

    public function getClientAddress(): AbstractAddress
    {
        return $this->connection->getClientAddress();
    }

    /**
     * @inheritdoc
     */
    public function connect(AbstractAddress $address)
    {
        $this->connection->connect($address);
        $this->reconnectSupport = true;
    }

    /**
     * @inheritdoc
     */
    public function onConnect(callable $callback): CallbackEventListener
    {
        return $this->connection->onConnect($callback);
    }

    /**
     * @inheritdoc
     */
    public function reconnect(): bool
    {
        return $this->connection->reconnect();
    }

    /**
     * @inheritdoc
     */
    public function isConnected(): bool
    {
        return $this->connection->isConnected();
    }

    /**
     * @inheritdoc
     */
    public function disconnect()
    {
        $this->connection->disconnect();
    }

    /**
     * @inheritdoc
     */
    public function onDisconnect(callable $callback): CallbackEventListener
    {
        return $this->connection->onDisconnect($callback);
    }

    /**
     * @inheritdoc
     */
    public function getConnectionResource(): AbstractConnectionResource
    {
        return $this->connection->getConnectionResource();
    }

    /**
     * @inheritdoc
     */
    public function read(): bool
    {
        return $this->protocol->read();
    }

    /**
     * @inheritdoc
     */
    public function returnRead()
    {
        return $this->protocol->returnRead();
    }

    /**
     * @inheritdoc
     */
    public function onReceive(callable $callback): CallbackEventListener
    {
        return $this->protocol->onReceive($callback);
    }

    /**
     * @inheritdoc
     */
    public function send($data): bool
    {
        return $this->protocol->send($data);
    }

    public function setTimeout(int $timeout)
    {
        $this->timeout = $timeout;
    }

    public function setReconnectInterval(int $interval)
    {
        $this->reconnectInterval = $interval;
    }

    public function getStatistic(): ClientStatistic
    {
        return new ClientStatistic(
            $this->connection->getReceivedBytesCount(),
            $this->connection->getReceivedPacketCount(),
            $this->connection->getTransmittedBytesCount(),
            $this->connection->getTransmittedPacketCount()
        );
    }

    /**
     * @inheritdoc
     */
    public function block()
    {
        if ($this->connection instanceof BlockingInterface) {
            $this->connection->block();
        }
    }

    /**
     * @inheritdoc
     */
    public function unblock()
    {
        if ($this->connection instanceof BlockingInterface) {
            $this->connection->unblock();
        }
    }

    /**
     * Поддерживает жизнь соединения.
     * Что делает:
     * - контролирует текущее состояние соединения,
     * - проверяет связь с заданным интервалом,
     * //     * - выполняет чтение входящих данных,
     * - выполняет переподключение при обрыве связи, если это включено (кроме серверного сокета),
     *
     * Возвращает true, если сокет жив, false если не работает.
     * Можно использовать в бесконечном цикле:
     * while ($NET->live()) {
     *     // тут делаем что-то.
     * }
     *
     * todo
     * @return bool живое соединение, или не живое
     */
    public function live(): bool
    {
        $alive = true;
        $time = time();

        if ($this->isConnected()) {
            if ($this->getTime() + $this->timeout <= $time) {
                $alive = false;
            } elseif (
                $this->getTime(self::TIME_LAST_PING) + $this->pingInterval <= $time
                && $this->getTime() + $this->pingInterval <= $time
            ) {
                // иногда пингуем соединение
                $this->ping();
                $this->resetTime(self::TIME_LAST_PING);
            }
        } elseif ($this->reconnectSupport) {
            if ($this->getTime(self::TIME_LAST_RECONNECT) + $this->reconnectInterval <= $time) {
                if ($this->reconnect()) {
                    $this->resetTime();
                }
            } else {
                $this->resetTime(self::TIME_LAST_RECONNECT);
            }
            // todo reconnect limit
        } else {
            $alive = false;
        }
        return $alive;
    }

    protected function ping()
    {
        if ($this->protocol instanceof PingSupportInterface) {
            $pingRequest = PingPacket::request(mt_rand(1, 9999));
            $this->protocol->pong(function (PingPacket $pingResponse) use ($pingRequest) {
                if ($pingResponse->isResponse() && $pingRequest->getValue() === $pingRequest->getValue()) {
                    $this->resetTime();
                } else {
                    throw new \RuntimeException(
                        'Unknown ping response value '
                        . $pingRequest->getValue() . ':' . $pingResponse->getValue()
                    );
                }
            });
            $this->protocol->ping($pingRequest);
        } else {
//            $this->protocol->send($pingRequest);
            throw new \LogicException('Protocol "' . get_class($this->protocol) . '" has no support ping.');
        }
    }

    protected function getTime(string $key = self::TIME_LAST_CHECK): int
    {
        return $this->times[$key] ?? 0;
    }

    protected function resetTime(string $key = self::TIME_LAST_CHECK)
    {
        $this->times[$key] = time();
    }
}
