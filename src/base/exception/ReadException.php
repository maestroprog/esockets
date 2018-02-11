<?php

namespace Esockets\base\exception;

final class ReadException extends \Exception
{
    const ERROR_EMPTY = 0; // nothing to read
    const ERROR_FAIL = 1; // not read to the end
    const ERROR_PROTOCOL = 2;

    /**
     * @param string $read
     * @param int $code
     */
    public function __construct($read, int $code)
    {
        parent::__construct($read, $code);
    }
}
