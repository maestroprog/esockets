<?php
/**
 * Created by PhpStorm.
 * User: yarullin
 * Date: 20.10.16
 * Time: 20:12
 */

namespace Esockets\io;

use Esockets\io\base\Aware;

class Dummy implements Aware
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
