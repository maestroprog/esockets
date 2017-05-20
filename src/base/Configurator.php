<?php

namespace Esockets\base;

use Esockets\base\exception\ConfiguratorException;
use Esockets\base\exception\ConnectionFactoryException;
use Esockets\Client;
use Esockets\Server;
use Esockets\socket\SocketFactory;

final class Configurator
{
    const CONNECTION_TYPE = 0;
    const CONNECTION_TYPE_SOCKET = 1;
    const CONNECTION_TYPE_CUSTOM = 7;
    const CONNECTION_CONFIG = 5;
    const FACTORY_CLASS = 2;
    const PROTOCOL_CLASS = 3;
    const ADDRESS = 4;

    private $connectionType;

    /**
     * @var AbstractConnectionFactory
     */
    private $connectionFactory;
    private $protocolClass;
    private $address;

    /**
     * @param array $config
     * @throws ConfiguratorException
     * @throws ConnectionFactoryException
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
    }

    private function initSocket(array $config)
    {
        $this->connectionFactory = new SocketFactory($config);
        $this->connectionType = self::CONNECTION_TYPE_SOCKET;
    }

    /**
     * @param array $config
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

    public function useProtocol(string $protocolClass)
    {
        if (!is_subclass_of($protocolClass, AbstractProtocol::class)) {
            throw new ConfiguratorException('Unknown protocol class: ' . $protocolClass . '.');
        }
        if (!class_exists($protocolClass)) {
            throw new ConfiguratorException('The protocol class "' . $protocolClass . '" is not exists.');
        }
        /*if (!is_subclass_of($protocolClass, AbstractProtocol::class)) {
            throw new ConfiguratorException('The class "' . $protocolClass . '" is not a protocol class.');
        }*/
        $this->protocolClass = $protocolClass;
    }

    public function connectToAddress(AbstractAddress $address): self
    {
        $this->address = $address;
        return $this;
    }

    public function makeClient(): Client
    {
        $client = $this->connectionFactory->makeClient();
        return new Client(
            $client,
            new $this->protocolClass($client)
        );
    }

    public function makeServer(): Server
    {
        return new Server(
            $this->connectionFactory->makeServer(),
            $this
        );
    }

    public function makePeer($connectionResource)
    {
        $peer = $this->connectionFactory->makePeer($connectionResource);
        return new Client(
            $peer,
            new $this->protocolClass($peer)
        );
    }

    /*
        public function getSocketType(): int
        {
            if (is_null($this->socketType)) {
                throw new ConfiguratorException('Socket type is not configured.');
            }
            return $this->socketType;
        }*/

    public function getAddress(): AbstractAddress
    {
        if (is_null($this->address) || !$this->address instanceof AbstractAddress) {
            throw new ConfiguratorException('Connection address is not configured.');
        }
        return $this->address;
    }
}