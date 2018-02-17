<?php

namespace Esockets\Base;

final class PingPacket
{
    private $value;
    private $response;

    private function __construct(int $value, bool $response)
    {
        $this->value = $value;
        $this->response = $response;
    }

    public static function request(int $value): self
    {
        return new self($value, false);
    }

    public static function response(int $value): self
    {
        return new self($value, true);
    }

    /**
     * Является ли данный пакет "понг" пакетом.
     */
    public function isResponse(): bool
    {
        return $this->response;
    }

    public function getValue(): int
    {
        return $this->value;
    }
}
