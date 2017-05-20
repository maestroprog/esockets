<?php

namespace Esockets\socket;

trait SocketTrait
{
    /**
     * @var int type of socket
     */
    protected $socketDomain;

    protected function isUnixAddress(): bool
    {
        return $this->socketDomain === AF_UNIX;
    }

    protected function isIpAddress(): bool
    {
        return $this->socketDomain === AF_INET || $this->socketDomain === AF_INET6;
    }
}
