<?php

namespace Esockets\Socket;

use Esockets\Base\AbstractAddress;

final class Ipv4Address extends AbstractAddress
{
    private $ip;
    private $port;

    public function __construct(string $ip, int $port)
    {
        if ($port < 0 || $port > 65535) {
            throw new \InvalidArgumentException('Invalid socket port.');
        }
        $this->ip = $ip;
        $this->port = $port;
    }

    public function getIp(): string
    {
        return $this->ip;
    }

    public function getPort(): int
    {
        return $this->port;
    }

    public function __toString(): string
    {
        return $this->ip . ':' . $this->port;
    }
}
