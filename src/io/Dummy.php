<?php
/**
 * Created by PhpStorm.
 * User: yarullin
 * Date: 20.10.16
 * Time: 20:12
 */

namespace maestroprog\esockets\io;


use maestroprog\esockets\io\base\Middleware;

class Dummy extends Middleware
{
    public function read(int $length, bool $need = false)
    {
        // nothing
    }

    public function send(string &$data)
    {
        // nothing
    }
}
