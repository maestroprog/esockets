<?php

namespace Esockets\socket;

use Esockets\base\AbstractAddress;
use Esockets\base\AbstractClient;
use Esockets\base\AbstractConnectionResource;
use Esockets\base\AbstractServer;
use Esockets\base\AbstractConnectionFactory;
use Esockets\base\exception\ConnectionFactoryException;
use Esockets\ClientsContainer;

final class SocketFactory extends AbstractConnectionFactory
{
    const SOCKET_DOMAIN = 'socket_domain';
    const SOCKET_PROTOCOL = 'socket_protocol';

    const DEFAULTS = [
        self::SOCKET_DOMAIN => AF_INET,
        self::SOCKET_PROTOCOL => SOL_TCP,
    ];

    private $socket_domain;
    private $socket_protocol;

    /**
     * @inheritDoc
     */
    public function __construct(array $params = [])
    {
        foreach (self::DEFAULTS as $varName => $defaultValue) {
            $this->{$varName} = $defaultValue;
        }

        foreach ($params as $param => $value) {
            switch ($param) {/*
                case self::TIMEOUT:
                case self::RECONNECT:
                    if (!is_int($value)) {
                        throw new ConnectionFactoryException($param . ' parameter must be is integer.');
                    }
                    // correcting the negative value
                    if ($value < 0) {
                        if ($param === self::RECONNECT && $value < -1) {
                            $value = -1;
                        } else {
                            $value = 0;
                        }
                    }
                    break;*/
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
                default:
                    throw new \Exception('Wrong parameter: ' . $param . '.');
            }
            $this->{$param} = $value;
        }
    }

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

    public function makeServer(): AbstractServer
    {
        if ($this->socket_protocol === SOL_TCP) {
            $client = new TcpServer($this->socket_domain, $this->makeErrorHandler(), new ClientsContainer());
        } elseif ($this->socket_protocol === SOL_UDP) {
            $client = new UdpServer($this->socket_domain, $this->makeErrorHandler(), new ClientsContainer());
        } else {
            throw new \LogicException('An attempt to use an unknown protocol.');
        }
        return $client;
    }

    public function makePeer(AbstractConnectionResource $connectionResource): AbstractClient
    {
        if ($this->socket_protocol === SOL_TCP) {
            $peer = TcpClient::createConnected($this->socket_domain, $this->makeErrorHandler(), $connectionResource);
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
