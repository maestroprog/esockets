<?php

class HttpRequest
{
    private $requestUri;

    public function __construct(string $requestUri)
    {
        $this->requestUri = $requestUri;
    }

    public function getRequestUri(): string
    {
        return $this->requestUri;
    }
}
