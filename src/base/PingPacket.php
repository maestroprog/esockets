<?php

namespace Esockets\base;

final class PingPacket
{
    private $value;
    private $response;

    public function __construct(int $value, bool $response)
    {
        $this->value = $value;
        $this->response = $response;
    }

    public function isResponse(): bool
    {
        return $this->response;
    }

    public function getValue(): int
    {
        return $this->value;
    }
}