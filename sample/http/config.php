<?php

use Esockets\base\Configurator;
use Esockets\net\NetFactory;

return [
    Configurator::CONNECTION_TYPE => Configurator::CONNECTION_TYPE_SOCKET,
    Configurator::CONNECTION_CONFIG => [
        NetFactory::SOCKET_DOMAIN => AF_INET,
        NetFactory::SOCKET_PROTOCOL => SOL_TCP,
    ],
    Configurator::PROTOCOL_CLASS => \Esockets\protocol\Easy::class,
];
