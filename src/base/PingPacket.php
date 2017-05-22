<?php

namespace Esockets\base;

final class PingPacket
{
    private $value;
    private $response;

    public static function request(int $value): self
    {
        return new self($value, false);
    }

    public static function response(int $value): self
    {
        return new self($value, true);
    }

    private function __construct(int $value, bool $response)
    {
        $this->value = $value;
        $this->response = $response;
    }

    /**
     * Является ли данный пакет "понг" пакетом.
     *
     * @return bool
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