<?php

namespace Esockets\Socket;

use Esockets\Base\AbstractClient;
use Esockets\Base\AbstractConnectionFactory;
use Esockets\Base\AbstractConnectionResource;
use Esockets\Base\AbstractServer;
use Esockets\Base\Exception\ConnectionFactoryException;
use Esockets\ClientsContainer;

/**
 * Фабрика объектов сокет сервера и клиента.
 */
final class SocketFactory extends AbstractConnectionFactory
{
    public const SOCKET_DOMAIN = 'socketDomain';
    public const SOCKET_TCP_MAX_CONN = 'socketMaxConn';
    public const SOCKET_PROTOCOL = 'socketProtocol';
    public const WAIT_INTERVAL = 'waitInterval';

    const DEFAULTS = [
        self::SOCKET_DOMAIN => AF_INET,
        self::SOCKET_PROTOCOL => SOL_TCP,
        self::SOCKET_TCP_MAX_CONN => 512,
        self::WAIT_INTERVAL => 1000,
    ];

    private $socketDomain;
    private $socketProtocol;
    private $socketMaxConn;
    private $waitInterval;

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
        if ($this->socketProtocol === SOL_TCP) {
            $client = TcpClient::createEmpty($this->socketDomain, $this->makeErrorHandler());
        } elseif ($this->socketProtocol === SOL_UDP) {
            $client = UdpClient::createEmpty($this->socketDomain, $this->makeErrorHandler());
        } else {
            throw new \LogicException('An attempt to use an unknown protocol.');
        }
        return $client;
    }

    /**
     * @return SocketErrorHandler
     * @throws \Esockets\Base\Exception\ConnectionException
     */
    protected function makeErrorHandler(): SocketErrorHandler
    {
        return new SocketErrorHandler();
    }

    /**
     * @inheritdoc
     */
    public function makeServer(): AbstractServer
    {
        if ($this->socketProtocol === SOL_TCP) {
            $client = new TcpServer(
                $this->socketDomain,
                $this->socketMaxConn,
                $this->waitInterval,
                $this->makeErrorHandler(),
                new ClientsContainer()
            );
        } elseif ($this->socketProtocol === SOL_UDP) {
            $client = new UdpServer($this->socketDomain, $this->makeErrorHandler(), new ClientsContainer());
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
        if ($this->socketProtocol === SOL_TCP) {
            $peer = TcpClient::createConnected(
                $this->socketDomain,
                $this->makeErrorHandler(),
                $connectionResource
            );
        } elseif ($this->socketProtocol === SOL_UDP) {
            $peer = UdpClient::createConnected(
                $this->socketDomain,
                $this->makeErrorHandler(),
                $connectionResource
            );
        } else {
            throw new \LogicException('An attempt to use an unknown protocol.');
        }
        $peer->unblock();
        return $peer;
    }
}
