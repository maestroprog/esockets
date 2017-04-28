<?php

namespace Esockets;


use Esockets\base\AbstractClient;

final class Client extends AbstractClient
{
    private $connection;

    public function __construct(AbstractClient $client)
    {
        $this->connection = $client;
    }
    
}