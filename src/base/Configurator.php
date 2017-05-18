<?php

namespace Esockets\base;

use Esockets\base\exception\ConfiguratorException;
use Esockets\base\exception\ConnectionFactoryException;
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
    const CONNECTION_LAYERS = 8;

    private $connectionType;
    private $connectionFactory;
    private $protocolClass;
    private $address;

    private $connectionLayers = [];

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
            $protocolClass = $config[self::PROTOCOL_CLASS];
            if (!is_subclass_of($protocolClass, AbstractProtocol::class)) {
                throw new ConfiguratorException('Unknown protocol class: ' . $protocolClass . '.');
            }
        }
        $connectionLayers = $config[self::CONNECTION_LAYERS] ?? [];
        if (!is_array($connectionLayers)) {
            throw new ConfiguratorException('Unknown CONNECTION_LAYERS config variable.');
        }
        foreach ($connectionLayers as $rowId => $layerConfig) {
            if (!is_array($layerConfig)) {
                throw new ConfiguratorException('Wrong connection layer config: ' . $rowId . '.');
            }
        }
    }

    private function initSocket(array $config)
    {
        $this->connectionFactory = new SocketFactory($config);
        $this->connectionType = self::CONNECTION_TYPE_SOCKET;
    }

    private function initCustom(array $config)
    {
        $this->connectionFactory =
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
        if (!class_exists($protocolClass)) {
            throw new ConfiguratorException('The protocol class "' . $protocolClass . '" is not exists.');
        }
        if (!is_subclass_of($protocolClass, AbstractProtocol::class)) {
            throw new ConfiguratorException('The class "' . $protocolClass . '" is not a protocol class.');
        }
        $this->protocolClass = $protocolClass;
    }

    public function connectToAddress(AbstractAddress $address): self
    {
        $this->address = $address;
        return $this;
    }

    public function makeClient(): AbstractClient
    {

    }

    public function makeServer(): AbstractServer
    {

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

    private function addConnectionLayer()
    {
        $this->connectionLayers[] = [$connectionClass, $protocolClass];
    }
}