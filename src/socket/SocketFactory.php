<?php

namespace Esockets\socket;

use Esockets\base\AbstractClient;
use Esockets\base\AbstractConnectionFactory;
use Esockets\base\AbstractConnectionResource;
use Esockets\base\AbstractServer;
use Esockets\base\exception\ConnectionFactoryException;
use Esockets\ClientsContainer;

/**
 * Фабрика объектов сокет сервера и клиента.
 */
final class SocketFactory extends AbstractConnectionFactory
{
    const SOCKET_DOMAIN = 'socket_domain';
    const SOCKET_TCP_MAX_CONN = 'socket_max_conn';
    const SOCKET_PROTOCOL = 'socket_protocol';
    const WAIT_INTERVAL = 'wait_interval';

    const DEFAULTS = [
        self::SOCKET_DOMAIN => AF_INET,
        self::SOCKET_PROTOCOL => SOL_TCP,
        self::SOCKET_TCP_MAX_CONN => 512,
        self::WAIT_INTERVAL => 1000,
    ];

    private $socket_domain;
    private $socket_protocol;
    private $socket_max_conn;
    private $wait_interval;

    /**
     * @inheritdoc
     */
    public function __construct(array $params = [])
    {
        foreach (self::DEFAULTS as $varName => $defaultValue) {
            $this->{$varName} = $defaultValue;
        }

        foreach ($params as $param => $value) {
            switch ($param) {
                case self::SOCKET_DOMAIN:
                    if (!in_array($value, [AF_INET, AF_UNIX])) {
                        throw new ConnectionFactoryException(
                            'Unknown ' . self::SOCKET_DOMAIN . ' value: ' . $value . '.'
                        );
                    }
                    break;
                case self::SOCKET_PROTOCOL:
                    if (!is_int($value) || !in_array($value, [SOL_TCP, SOL_UDP])) {
                        throw new ConnectionFactoryException(
                            'The ' . self::SOCKET_PROTOCOL . ' "' . $value . '" is not supported.'
                        );
                    }
                    break;
                case self::WAIT_INTERVAL:
                    if (!is_int($value) || $value <= 0) {
                        throw new ConnectionFactoryException('The WAIT_INTERVAL must be bigger than 0.');
                    }
                    break;
                default:
                    throw new ConnectionFactoryException('Wrong parameter: ' . $param . '.');
            }
            $this->{$param} = $value;
        }
    }

    /**
     * @inheritdoc
     */
    public function makeClient(): AbstractClient
    {
        if ($this->socket_protocol === SOL_TCP) {
            $client = TcpClient::createEmpty($this->socket_domain, $this->makeErrorHandler());
        } elseif ($this->socket_protocol === SOL_UDP) {
            $client = UdpClient::createEmpty($this->socket_domain, $this->makeErrorHandler());
        } else {
            throw new \LogicException('An attempt to use an unknown protocol.');
        }
        return $client;
    }

    /**
     * @inheritdoc
     */
    public function makeServer(): AbstractServer
    {
        if ($this->socket_protocol === SOL_TCP) {
            $client = new TcpServer(
                $this->socket_domain,
                $this->socket_max_conn,
                $this->wait_interval,
                $this->makeErrorHandler(),
                new ClientsContainer()
            );
        } elseif ($this->socket_protocol === SOL_UDP) {
            $client = new UdpServer($this->socket_domain, $this->makeErrorHandler(), new ClientsContainer());
        } else {
            throw new \LogicException('An attempt to use an unknown protocol.');
        }
        return $client;
    }

    /**
     * @inheritdoc
     */
    public function makePeer(AbstractConnectionResource $connectionResource): AbstractClient
    {
        if ($this->socket_protocol === SOL_TCP) {
            $peer = TcpClient::createConnected(
                $this->socket_domain,
                $this->makeErrorHandler(),
                $connectionResource
            );
        } elseif ($this->socket_protocol === SOL_UDP) {
            $peer = UdpClient::createConnected(
                $this->socket_domain,
                $this->makeErrorHandler(),
                $connectionResource
            );
        } else {
            throw new \LogicException('An attempt to use an unknown protocol.');
        }
        $peer->unblock();
        return $peer;
    }

    protected function makeErrorHandler(): SocketErrorHandler
    {
        return new SocketErrorHandler();
    }
}
