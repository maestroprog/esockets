<?php
/**
 * Created by PhpStorm.
 * User: Руслан
 * Date: 16.10.2016
 * Time: 22:04
 */

namespace maestroprog\esockets\io\base;

use maestroprog\esockets\protocol\base\ProtocolAware;
use maestroprog\esockets\protocol\Easy;

class IOProvider
{
    const PROTOCOLS_KNOW = [
        Easy::class,
    ];

    /**
     * @var ProtocolAware
     */
    private $protocol;

    /**
     * @var IOMiddleware Класс-посредник между нами, и настоящим владельцем ввода-вывода. :)
     * @todo так-то он нам не нужен, надо бы убрать его отсюда.
     */
    private $middle;

    public function __construct(string $protocolName, IOMiddleware $middle)
    {
        if (!isset($protocolName, $this::PROTOCOLS_KNOW) && !class_exists($protocolName)) {
            throw new \Exception('I don\'t know the protocol');
        }
        $this->protocol = new $protocolName($middle);
        $this->middle = $middle;
    }

    /**
     * Параметр $createEvent = true говорит о том, что после чтения данных
     *      нужно создать событие, и передать туда прочтенные данные.
     *      При $createEvent = false прочтенные данные нужно вернуть из функции.
     *
     * @param bool $need
     */
    public function read(bool $createEvent = true, bool $need = false)
    {
        if ($this->protocol) {
            $this->protocol->read($need);
        } else {
            $this->read($need);
        }
    }
}
