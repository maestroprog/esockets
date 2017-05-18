<?php

namespace Esockets\socket;

use Esockets\base\AbstractServer;

class UdpServer extends AbstractServer
{
    protected $errorHandler;

    public function __construct(int $socketDomain, SocketErrorHandler $errorHandler)
    {
        $this->errorHandler = $errorHandler;
        $this->errorHandler->setSocket($this->socket);
    }

}