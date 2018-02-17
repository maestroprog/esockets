<?php

use Esockets\Base\Configurator;
use Esockets\Debug\LoggingProtocol;
use Esockets\Protocol\EasyStream;
use Esockets\Socket\SocketFactory;

return [
    Configurator::CONNECTION_TYPE => Configurator::CONNECTION_TYPE_SOCKET,
    Configurator::CONNECTION_CONFIG => [
        SocketFactory::SOCKET_DOMAIN => AF_INET,
        SocketFactory::SOCKET_PROTOCOL => SOL_TCP,
    ],
    Configurator::PROTOCOL_CLASS => LoggingProtocol::withRealProtocolClass(EasyStream::class),
    Configurator::PING_INTERVAL => 30,
];
