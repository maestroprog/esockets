<?php

namespace Esockets;

use Esockets\base\AbstractAddress;
use Esockets\base\AbstractClient;
use Esockets\base\AbstractProtocol;
use Esockets\base\ConnectionWrapperInterface;
use Esockets\base\PingPacket;
use Esockets\base\PingSupportInterface;

class Client extends AbstractClient implements ConnectionWrapperInterface
{
    private $connection;
    private $protocol;

    private $timeout;
    private $reconnect;

    const TIME_LAST_PING = 'last_ping';
    const TIME_LAST_RECONNECT = 'last_reconnect';
    const TIME_LAST_CHECK = 'last_check';

    private $times = [];

    public function __construct(AbstractClient $client, AbstractProtocol $protocol)
    {
        $this->connection = $client;
        $this->protocol = $protocol;
    }

    public function setTimeout(int $timeout)
    {
        $this->timeout = $timeout;
    }

    public function setReconnectInterval(int $reconnect)
    {
        $this->reconnect = $reconnect;
    }

    public function getServerAddress(): AbstractAddress
    {
        return $this->connection->getServerAddress();
    }

    public function getClientAddress(): AbstractAddress
    {
        return $this->connection->getClientAddress();
    }

    public function connect(AbstractAddress $address)
    {
        $this->connection->connect($address);
    }

    public function onConnect(callable $callback)
    {
        $this->connection->onConnect($callback);
    }

    public function reconnect(): bool
    {
        return $this->connection->reconnect();
    }

    public function isConnected(): bool
    {
        return $this->connection->isConnected();
    }

    public function disconnect()
    {
        $this->connection->disconnect();
    }

    public function onDisconnect(callable $callback)
    {
        $this->connection->onDisconnect($callback);
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
            throw new \LogicException('Protocol ' . get_class($this->protocol) . ' has no support ping.');
        }
    }

    public function read()
    {
        $this->protocol->read();
    }

    public function returnRead()
    {
        return $this->protocol->returnRead();
    }

    public function onReceive(callable $callback)
    {
        $this->protocol->onReceive($callback);
    }

    public function send($data): bool
    {
        return $this->protocol->send($data);
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
        } elseif ($this->reconnect >= 0 && $this->getTime() + $this->timeout > time()) {
            if ($this->getTime(self::TIME_LAST_RECONNECT) + $this->reconnect <= time()) {
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

    protected function getTime(string $key = self::TIME_LAST_CHECK): int
    {
        return $this->times[$key] ?? 0;
    }

    protected function setTime(string $key = self::TIME_LAST_CHECK)
    {
        $this->times[$key] = time();
    }
}
