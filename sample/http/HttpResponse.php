<?php

class HttpResponse
{
    private $code;
    private $status;
    private $body;
    private $headers;

    public function __construct(int $code, string $status, string $body, array $headers = [])
    {
        $this->code = $code;
        $this->status = $status;
        $this->body = $body;
        $this->headers = $headers;
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

    public function getHeaders(): string
    {
        return implode("\r\n", array_merge([
            sprintf('HTTP/1.0 %d %s', $this->getCode(), $this->getStatus()),
            sprintf('Content-Length: %d', strlen($this->getBody())),
            'Connection: close'
        ], $this->headers));
    }
}
