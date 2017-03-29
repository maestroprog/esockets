<?php
/**
 * Created by PhpStorm.
 * User: yarullin
 * Date: 20.10.16
 * Time: 20:12
 */

namespace Esockets\io;

use Esockets\io\base\IoAwareInterface;

/**
 * Класс-заглушка ввода-вывода.
 */
final class Dummy implements IoAwareInterface
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
