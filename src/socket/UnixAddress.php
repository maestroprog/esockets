<?php

namespace Esockets\socket;

use Esockets\base\AbstractAddress;

final class UnixAddress extends AbstractAddress
{
    private $sockPath;

    public function __construct(string $sockPath)
    {
        $this->sockPath = $sockPath;
    }

    public function getSockPath(): string
    {
        return $this->sockPath;
    }

    public function equalsTo(AbstractAddress $address): bool
    {
        return $this->__toString() === $address->__toString();
    }

    public function __toString(): string
    {
        return $this->sockPath;
    }
}
