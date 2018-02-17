<?php

namespace Esockets\Base\Exception;

/**
 * Исключение, возникающее при проблемах с соединением.
 */
final class ConnectionException extends \Exception
{
    const ERROR_UNKNOWN = 0;
    const ERROR_FAIL_CREATE = 1;
    const ERROR_FAIL_CONNECT = 2;
}
