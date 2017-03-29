<?php
/**
 * Created by PhpStorm.
 * User: maestroprog
 * Date: 21.03.2017
 * Time: 15:53
 */

namespace Esockets\base;


final class Config
{
    private $type;
    private $address;
    private $port;

    public function __construct()
    {
    }

    public function withSocketType(int $type): self
    {
        if (!in_array($type, [AF_UNIX, AF_INET, AF_INET6])) {
            throw new \Exception('Unknown socket type.');
        }
        $this->type = $type;
        return $this;
    }

    public function useAddress(string $address): self
    {
        $this->address = $address;
        return $this;
    }

    public function onPort(int $port): self
    {
        if ($port < 0 || $port > 65535) {
            throw new \Exception('Invalid socket port.');
        }
        $this->port = $port;
        return $this;
    }

    public function getType(): int
    {
        if (is_null($this->type)) {
            throw new \Exception('Socket type is not configured.');
        }
        return $this->type;
    }

    public function getAddress(): string
    {
        if (is_null($this->address)) {
            throw new \Exception('Socket address is not configured.');
        }
        return $this->address;
    }

    public function getPort(): int
    {
        if (is_null($this->port)) {
            throw new \Exception('Socket port is not configured.');
        }
        return $this->port;
    }
}