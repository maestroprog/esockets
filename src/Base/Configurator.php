<?php

namespace Esockets\Base;

use Esockets\Base\Exception\ConfiguratorException;
use Esockets\Base\Exception\ConnectionFactoryException;
use Esockets\Client;
use Esockets\Server;
use Esockets\Socket\SocketFactory;

final class Configurator
{
    public const CONNECTION_TYPE = 0;
    public const CONNECTION_TYPE_SOCKET = 1;
    public const CONNECTION_TYPE_CUSTOM = 7;
    public const CONNECTION_CONFIG = 5;

    public const CONNECTION_TIMEOUT = 6;
    public const PING_INTERVAL = 7;
    public const RECONNECT_INTERVAL = 8;

    public const FACTORY_CLASS = 2;
    public const PROTOCOL_CLASS = 3;
    public const ADDRESS = 4;

    private $connectionType;

    /**
     * @var AbstractConnectionFactory
     */
    private $connectionFactory;
    /**
     * @var AbstractProtocol|string
     */
    private $protocolClass;
    private $address;
    private $connectionTimeout = 60;
    private $pingInterval = 30;
    private $reconnectInterval = 5;

    /**
     * @param array $config
     *
     * @throws ConfiguratorException
     */
    public function __construct(array $config = [])
    {
        if (isset($config[self::CONNECTION_TYPE])) {
            $connectionType = $config[self::CONNECTION_TYPE];
            $connectionConfig = $config[self::CONNECTION_CONFIG] ?? [];
            switch ($connectionType) {
                case self::CONNECTION_TYPE_SOCKET:
                    $this->initSocket($connectionConfig);
                    break;
                case self::CONNECTION_TYPE_CUSTOM:
                    $this->initCustom($connectionConfig);
                    break;
                default:
                    throw new ConfiguratorException('Unknown CONNECTION_TYPE: ' . $connectionType . '.');
            }
        }
        if (isset($config[self::PROTOCOL_CLASS])) {
            $this->useProtocol($config[self::PROTOCOL_CLASS]);
        }
        if (isset($config[self::CONNECTION_TIMEOUT])) {
            $this->connectionTimeout = (int)$config[self::CONNECTION_TIMEOUT];
        }
        if (isset($config[self::PING_INTERVAL])) {
            $this->pingInterval = (int)$config[self::PING_INTERVAL];
        }
        if (isset($config[self::RECONNECT_INTERVAL])) {
            $this->reconnectInterval = (int)$config[self::RECONNECT_INTERVAL];
        }
    }

    /**
     * @param array $config
     *
     * @throws ConnectionFactoryException
     */
    private function initSocket(array $config)
    {
        $this->connectionFactory = new SocketFactory($config);
        $this->connectionType = self::CONNECTION_TYPE_SOCKET;
    }

    /**
     * @param array $config
     *
     * @throws ConfiguratorException
     * @todo пока не работоспособно!
     */
    private function initCustom(array $config)
    {
        throw new \LogicException('It\'s not working');
        //$this->connectionFactory =
        if (!class_exists($clientClass)) {
            throw new ConfiguratorException('The connection class "' . $clientClass . '" is not exists.');
        }
        if (!$clientClass instanceof IoAwareInterface) {
            if (!class_exists($clientClass)) {
                throw new ConfiguratorException('The class "' . $clientClass . '" is not a connection class.');
            }
        }
        $this->connectionType = self::CONNECTION_TYPE_CUSTOM;
        $this->clientClass = $clientClass;
    }

    /**
     * @param string $protocolClass
     *
     * @throws ConfiguratorException
     */
    public function useProtocol(string $protocolClass)
    {
        if (!is_subclass_of($protocolClass, AbstractProtocol::class)) {
            throw new ConfiguratorException('Unknown protocol class: ' . $protocolClass . '.');
        }
        if (!class_exists($protocolClass)) {
            throw new ConfiguratorException('The protocol class "' . $protocolClass . '" is not exists.');
        }
        $this->protocolClass = $protocolClass;
    }

    public function connectToAddress(AbstractAddress $address): self
    {
        $this->address = $address;
        return $this;
    }

    /**s
     *
     * @return Client
     */
    public function makeClient(): Client
    {
        $client = $this->connectionFactory->makeClient();
        return new Client(
            $client,
            $this->protocolClass::create($client),
            $this->connectionTimeout,
            $this->pingInterval,
            $this->reconnectInterval
        );
    }

    public function makeServer(): Server
    {
        return new Server(
            $this->connectionFactory->makeServer(),
            $this
        );
    }

    public function makePeer(AbstractConnectionResource $connectionResource)
    {
        $peer = $this->connectionFactory->makePeer($connectionResource);
        return new Client(
            $peer,
            $this->protocolClass::create($peer),
            $this->connectionTimeout,
            $this->pingInterval,
            $this->reconnectInterval
        );
    }

    /**
     * @return AbstractAddress
     *
     * @throws ConfiguratorException
     */
    public function getAddress(): AbstractAddress
    {
        if (null === $this->address || !$this->address instanceof AbstractAddress) {
            throw new ConfiguratorException('Connection address is not configured.');
        }
        return $this->address;
    }
}
