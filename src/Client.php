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
    private $reconnectInterval = 1;

    const TIME_LAST_PING = 'last_ping';
    const TIME_LAST_RECONNECT = 'last_reconnect';
    const TIME_LAST_CHECK = 'last_check';

    private $times = [];

    public function __construct(AbstractClient $connection, AbstractProtocol $protocol)
    {
        $this->connection = $connection;
        $this->protocol = $protocol;
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
     * - выполняет чтение входящих данных,
     * - выполняет переподключение при обрыве связи, если это включено,
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
        if ($this->isConnected()) {
            $this->setTime();
            if (($this->getTime(self::TIME_LAST_PING) + $this->timeout * 2) <= time()) {
                // иногда пингуем соединение
                $this->ping();
            }
        } elseif ($this->reconnectInterval >= 0 && $this->getTime() + $this->timeout > time()) {
            if ($this->getTime(self::TIME_LAST_RECONNECT) + $this->reconnectInterval <= time()) {
                if ($this->reconnect()) {
                    $this->setTime();
                }
            } else {
                $this->setTime(self::TIME_LAST_RECONNECT);
            }
        } else {
            $alive = false;
        }
        return $alive;
    }

    protected function ping()
    {
        $pingRequest = PingPacket::request(mt_rand(1, 9999));
        if ($this->protocol instanceof PingSupportInterface) {
            $this->protocol->pong(function (PingPacket $pingResponse) use ($pingRequest) {
                if ($pingResponse->isResponse() && $pingRequest->getValue() === $pingRequest->getValue()) {
                    $this->setTime(self::TIME_LAST_PING);
                } else {
                    trigger_error(
                        'Unknown ping response value '
                        . $pingRequest->getValue() . ':' . $pingResponse->getValue(),
                        E_USER_WARNING
                    );
                }
            });
            $this->protocol->ping($pingRequest);
        } else {
            $this->protocol->send($pingRequest);
            throw new \LogicException('HttpProtocol ' . get_class($this->protocol) . ' has no support ping.');
        }
    }

    protected function getTime(string $key = self::TIME_LAST_CHECK): int
    {
        return $this->times[$key] ?? 0;
    }

    protected function setTime(string $key = self::TIME_LAST_CHECK)
    {
        $this->times[$key] = time();
    }
}
