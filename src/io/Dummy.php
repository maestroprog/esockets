<?php

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
