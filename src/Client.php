<?php
/**
 * Created by PhpStorm.
 * User: maestroprog
 * Date: 21.03.2017
 * Time: 15:22
 */

namespace Esockets;


use Esockets\base\ClientInterface;

final class Client implements ClientInterface
{
    private $connection;

    public function __construct(ClientInterface $client)
    {
        $this->connection = $client;
    }
}