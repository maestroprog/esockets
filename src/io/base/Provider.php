<?php
/**
 * Created by PhpStorm.
 * User: Руслан
 * Date: 16.10.2016
 * Time: 22:04
 */

namespace maestroprog\esockets\io\base;

use maestroprog\esockets\protocol\base\Aware;
use maestroprog\esockets\protocol\Dummy;
use maestroprog\esockets\protocol\Easy;

/**
 * Класс-"поставщик" интерфейса ввода/вывода для протокола.
 * Через этот класс идёт весь ввод/вывод.
 * Этот класс нужно создавать для использования в качестве интерфейса ввода/вывода!
 * То есть
 * @example     $provider = new Provider(Dummy::class, new Dummy());
 */
class Provider
{
    const PROTOCOLS_KNOW = [
        Dummy::class,
        Easy::class,
    ];

    /**
     * @var Aware
     */
    private $protocol;

    /**
     * @var Middleware Класс-посредник между нами, и настоящим владельцем ввода-вывода. :)
     * @todo так-то он нам не нужен, надо бы убрать его отсюда.
     */
    private $middle;

    public function __construct(string $protocolName, Middleware $middle)
    {
        if (!in_array($protocolName, self::PROTOCOLS_KNOW) && !class_exists($protocolName)) {
            throw new \Exception('I don\'t know the protocol');
        }
        $this->protocol = new $protocolName($middle);
        $this->middle = $middle;
    }

    /**
     * @param bool $need
     * @return mixed
     */
    public function read(bool $need = false)
    {
        if ($this->protocol) {
            return $this->protocol->read($need);
        }
        return false;
    }

    /**
     * @param mixed $data
     * @return mixed
     */
    public function send(&$data)
    {
        if ($this->protocol) {
            return $this->protocol->send($data);
        }
        return false;
    }
}
