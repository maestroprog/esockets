<?php

namespace Esockets;

use Esockets\Base\AbstractAddress;
use Esockets\Base\AbstractClient;
use Esockets\Base\AbstractConnectionResource;
use Esockets\Base\AbstractProtocol;
use Esockets\Base\BlockingInterface;
use Esockets\Base\CallbackEventListener;
use Esockets\Base\ConnectorInterface;
use Esockets\Base\Exception\SendException;
use Esockets\Base\PingPacket;
use Esockets\Base\PingSupportInterface;
use Esockets\Base\ReaderInterface;
use Esockets\Base\SenderInterface;

/**
 * Враппер над связкой протокол-клиент.
 */
class Client implements ConnectorInterface, ReaderInterface, SenderInterface, BlockingInterface
{
    protected const TIME_LAST_CHECK = 'last_check';
    protected const TIME_LAST_PING = 'last_ping';
    protected const TIME_LAST_RECONNECT = 'last_reconnect';

    private $connection;
    private $protocol;
    private $timeout;
    private $pingInterval; // время успешной работы соединения
    private $reconnectInterval; // время последнего пинга
    private $reconnectSupport = false; // время последней попытки реконнекта
    private $times = [];

    public function __construct(
        AbstractClient $connection,
        AbstractProtocol $protocol,
        int $timeout,
        int $pingInterval,
        int $reconnectInterval
    )
    {
        $this->connection = $connection;
        $this->protocol = $protocol;
        $this->timeout = $timeout;
        $this->pingInterval = $pingInterval;
        $this->reconnectInterval = $reconnectInterval;

        $this->resetTime();
        $this->protocol->onReceive(function () {
            $this->resetTime();
        });
        if ($this->protocol instanceof PingSupportInterface) {
            $this->protocol->onPingReceived(function (PingPacket $ping) {
                $this->resetTime();
                $this->resetTime(self::TIME_LAST_PING);
            });
        }
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
    public function connect(AbstractAddress $address): void
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
    public function disconnect(): void
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

    public function ready(): bool
    {
        if (!$this->isConnected()) {
            return false;
        }

        return $this->connection->ready();
    }

    /**
     * @inheritdoc
     */
    public function read(): bool
    {
        if (!$this->isConnected()) {
            return false;
        }

        try {
            return $this->protocol->read();
        } catch (SendException $e) {
            if ($this->isConnected()) {
                $this->disconnect();
            }

            return false;
        }
    }

    /**
     * @inheritdoc
     */
    public function returnRead()
    {
        if (!$this->isConnected()) {
            return false;
        }

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
        if (!$this->isConnected()) {
            return false;
        }
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
    public function block(): void
    {
        if ($this->connection instanceof BlockingInterface) {
            $this->connection->block();
        }
    }

    /**
     * @inheritdoc
     */
    public function unblock(): void
    {
        if ($this->connection instanceof BlockingInterface) {
            $this->connection->unblock();
        }
    }

    public function isBlocked(): bool
    {
        if ($this->connection instanceof BlockingInterface) {
            return $this->connection->isBlocked();
        }

        return false;
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
                } else {
                    sleep($this->reconnectInterval);
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

    /**
     * @inheritdoc
     */
    public function isConnected(): bool
    {
        return $this->connection->isConnected();
    }

    protected function getTime(string $key = self::TIME_LAST_CHECK): int
    {
        return $this->times[$key] ?? 0;
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
        }
    }

    protected function resetTime(string $key = self::TIME_LAST_CHECK)
    {
        $this->times[$key] = time();
    }
}
