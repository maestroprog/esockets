<?php
/**
 * Created by PhpStorm.
 * User: yarullin
 * Date: 19.05.2017
 * Time: 17:18
 */

namespace Esockets\vpn;


use Esockets\base\AbstractClient;
use Esockets\base\AbstractConnectionFactory;
use Esockets\base\AbstractServer;
use Esockets\base\exception\ConnectionFactoryException;

class VpnFactory extends AbstractConnectionFactory
{
    public function __construct(array $params = [])
    {
        parent::__construct($params);
    }

    public function makeClient(): AbstractClient
    {
        // TODO: Implement makeClient() method.
    }

    public function makeServer(): AbstractServer
    {
        // TODO: Implement makeServer() method.
    }
}
