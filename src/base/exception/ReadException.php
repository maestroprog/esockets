<?php

/**
 * Created by PhpStorm.
 * User: maestroprog
 * Date: 22.03.2017
 * Time: 19:56
 */

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