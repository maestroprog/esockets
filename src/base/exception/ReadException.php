<?php

namespace Esockets\base\exception;

final class ReadException extends \Exception
{
    const ERROR_EMPTY = 0; // nothing to read
    const ERROR_FAIL = 1; // not read to the end
    const ERROR_PROTOCOL = 2;

    /**
     * @param string $read
     */
    public function __construct($read)
    {
        parent::__construct($read);
    }
}
