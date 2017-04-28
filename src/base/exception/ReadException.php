<?php

namespace Esockets\base\exception;

class ReadException extends \Exception
{
    /**
     * @param string $read
     */
    public function __construct($read)
    {
    }
}