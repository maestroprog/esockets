<?php

class HttpResponse
{
    private $code;
    private $status;
    private $body;

    public function __construct(int $code, string $status, string $body)
    {
        $this->code = $code;
        $this->status = $status;
        $this->body = $body;
    }

    public function getCode(): int
    {
        return $this->code;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getBody(): string
    {
        return $this->body;
    }
}
