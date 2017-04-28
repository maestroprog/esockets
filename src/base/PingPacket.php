<?php

namespace Esockets\base;


final class PingPacket
{
    private $value;
    private $response;

    public function request(int $value)
    {
        return new self($value, false);
    }

    public function response(int $value)
    {
        return new self($value, true);
    }

    private function __construct(int $value, bool $response)
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