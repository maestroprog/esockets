<?php

use Esockets\base\Configurator;
use Esockets\socket\SocketFactory;

return [
    Configurator::CONNECTION_TYPE => Configurator::CONNECTION_TYPE_SOCKET,
    Configurator::CONNECTION_CONFIG => [
        SocketFactory::SOCKET_DOMAIN => AF_INET,
        SocketFactory::SOCKET_PROTOCOL => SOL_TCP,
    ],
    Configurator::CONNECTION_LAYERS => [],
    Configurator::PROTOCOL_CLASS => \Esockets\protocol\Easy::class,
];
