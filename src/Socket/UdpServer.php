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
use Esockets\Base\Exception\ReadException;
use Esockets\Base\HasClientsContainer;

/**
 * Костыльная реализация UDP сервера.
 */
final class UdpServer extends AbstractServer implements BlockingInterface, HasClientsContainer
{
    use SocketTrait;

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
     * @param int $socketDomain
     * @param SocketErrorHandler $errorHandler
     * @param ClientsContainerInterface $clientsContainer
     *
     * @throws ConnectionException
     */
    public function __construct(
        int $socketDomain,
        SocketErrorHandler $errorHandler,
        ClientsContainerInterface $clientsContainer
    )
    {
        $this->socketDomain = $socketDomain;
        $this->errorHandler = $errorHandler;
        $this->clientsContainer = $clientsContainer;

        $this->eventConnect = new Event();
        $this->eventDisconnect = new Event();
        $this->eventFound = new Event();

        if (!($this->socket = socket_create($socketDomain, SOCK_DGRAM, SOL_UDP))) {
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
        // todo idempotency
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
            $this->unblock();
        }
    }

    /**
     * @inheritdoc
     */
    public function unblock(): void
    {
        socket_set_nonblock($this->socket);
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
        return $this->socket;
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
        $select = [$this->socket];
        $r = $w = [];
        // ждем наличие активности на сокете
        if (socket_select($select, $r, $w, 1)) {
            $address = null;
            $port = 0;
            // пытаемся прочитать данные из сокета
            while (false !== ($bytes = socket_recvfrom($this->socket, $buffer, 65535, 0, $address, $port))) {
                if ($bytes === 0) {
                    throw new ReadException('0 bytes read from udp socket.', ReadException::ERROR_EMPTY);
                }

                if ($this->isIpAddress()) {
                    $clientAddress = new Ipv4Address($address, $port);
                } else {
                    $clientAddress = new UnixAddress($address);
                }

                if (!$this->clientsContainer->existsByAddress($clientAddress)) {
                    // если до сих пор данные с такого адреса не приходили в сокет,
                    // создадим виртуального серверного клиента
                    $this->eventFound->call(new VirtualUdpConnection(
                        $this->socketDomain,
                        new SocketConnectionResource(
                            $this->socket
                        ),
                        $clientAddress,
                        []
                    ));
                } elseif (!($bytes === 1 && $buffer == 1)) {
                    // "1" - это сообщение-приветствие при "подключении" к удаленному udp сокету
                    // если пришло сообщение от известного адреса (клиента)
                    $client = $this->clientsContainer->getByAddress($clientAddress);
                    $connectionResource = $client->getConnectionResource();
                    if (!$connectionResource instanceof VirtualUdpConnection) {
                        throw new \LogicException('Unknown connection resource.');
                    }
                    // добавляем прочитанные данные в буфер виртуального клиента
                    $connectionResource->addToBuffer($buffer);
                }
            }
        }
        // теперь организуем чтение из буферов виртуальных клиентов
        foreach ($this->clientsContainer->list() as $client) {
            $connectionResource = $client->getConnectionResource();
            if (!$connectionResource instanceof VirtualUdpConnection) {
                // в качестве клиентов могут быть только такие костыльные клиенты
                throw new \LogicException('Unknown connection resource.');
            }
            if ($connectionResource->getBufferLength() > 0) {
                // если в буфере клиента что-то есть, прочитаем
                $client->read();
            }
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

    /**
     * @inheritdoc
     */
    public function block(): void
    {
        socket_set_block($this->socket);
    }
}
