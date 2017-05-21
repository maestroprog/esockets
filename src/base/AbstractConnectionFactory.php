<?php

namespace Esockets\base;

use Esockets\base\exception\ConnectionFactoryException;

abstract class AbstractConnectionFactory
{
    /**
     * @param array $params
     * @throws ConnectionFactoryException
     */
    abstract public function __construct(array $params = []);

    abstract public function makeClient(): AbstractClient;

    abstract public function makeServer(): AbstractServer;

    abstract public function makePeer($connectionResource, AbstractAddress $peerAddress = null): AbstractClient;
}
